<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Application\DTO\SaveFormCommand;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис нормализации целевых настроек формы.
 */
final class FormTargetResolverService
{
    private FormFieldTypeRegistryService $fieldTypeRegistry;
    private FormRuleCompilerService $ruleCompiler;

    /**
     * Инициализирует объект и его зависимости.
     *
     * @param ?FormFieldTypeRegistryService $fieldTypeRegistry
     * @param ?FormRuleCompilerService $ruleCompiler
     */
    public function __construct(
        ?FormFieldTypeRegistryService $fieldTypeRegistry = null,
        ?FormRuleCompilerService $ruleCompiler = null,
    ) {
        $this->fieldTypeRegistry = $fieldTypeRegistry ?? new FormFieldTypeRegistryService();
        $this->ruleCompiler = $ruleCompiler ?? new FormRuleCompilerService();
    }

    /**
     * Нормализует from request.
     *
     * @param array $form
     *
     * @return array
     */
    public function normalizeFromRequest(array $form): array
    {
        return [
            'schema' => $this->normalizeSchema(is_array($form['schema'] ?? null) ? $form['schema'] : []),
        ];
    }

    /**
     * Нормализует command.
     *
     * @param SaveFormCommand $command
     *
     * @return SaveFormCommand
     */
    public function normalizeCommand(SaveFormCommand $command): SaveFormCommand
    {
        return new SaveFormCommand(
            $command->id,
            $command->name,
            $command->code,
            $command->active,
            $command->archived,
            $this->normalizeSchema($command->schema),
            $command->userId,
            $command->createVersion,
        );
    }

    /**
     * Дополняет массив формы нормализованными настройками оформления.
     *
     * @param array $item
     *
     * @return array
     */
    public function decorateFormArray(array $item): array
    {
        $item['schema'] = $this->normalizeSchema(is_array($item['schema'] ?? null) ? $item['schema'] : []);
        unset(
            $item['smartProcessEntityTypeId'],
            $item['smartProcessCategoryId'],
            $item['smartProcessStageId'],
            $item['smartProcessTitleMode'],
            $item['smartProcessTitleFieldCode'],
            $item['smartProcessTitleTemplate'],
            $item['smartProcessUpdateMode'],
            $item['smartProcessUpdateItemIdFieldCode'],
            $item['smartProcessExternalFieldName'],
            $item['smartProcessExternalValueFieldCode'],
            $item['smartProcessCreateIfNotFound']
        );

        return $item;
    }


    /**
     * Нормализует значение.
     *
     * @param array $data
     *
     * @return array
     */
    public function normalize(array $data): array
    {
        if (array_key_exists('schema', $data)) {
            $data['schema'] = $this->normalizeSchema(is_array($data['schema'] ?? null) ? $data['schema'] : []);
            return $data;
        }

        return $this->normalizeSchema($data);
    }

    /**
     * Нормализует схемы.
     *
     * @param array $schema
     *
     * @return array
     */
    public function normalizeSchema(array $schema): array
    {
        $schema['version'] = max(2, (int)($schema['version'] ?? 2));
        $schema['schemaVersion'] = max(2, (int)($schema['schemaVersion'] ?? $schema['version']));
        $schema['layout'] = $this->normalizeLayout(is_array($schema['layout'] ?? null) ? $schema['layout'] : []);
        $schema['settings'] = is_array($schema['settings'] ?? null) ? $schema['settings'] : [];
        unset($schema['settings']['entitySource'], $schema['settings']['customSubmit']);
        $schema['sections'] = is_array($schema['sections'] ?? null) && !empty($schema['sections'])
            ? $this->normalizeSections($schema['sections'])
            : [];
        $schema['fields'] = $this->normalizeFields(is_array($schema['fields'] ?? null) ? $schema['fields'] : []);
        $schema = $this->ruleCompiler->compileSchema($schema);

        return $schema;
    }

    /**
     * Возвращает поля типа definitions.
     *
     * @return array
     */
    public function getFieldTypeDefinitions(): array
    {
        return $this->fieldTypeRegistry->getTypeDefinitions();
    }

    /**
     * Возвращает правила operators.
     *
     * @return array
     */
    public function getRuleOperators(): array
    {
        return $this->ruleCompiler->getOperators();
    }

    /**
     * Возвращает правила действий.
     *
     * @return array
     */
    public function getRuleActions(): array
    {
        return $this->ruleCompiler->getActions();
    }


    /**
     * Нормализует layout.
     *
     * @param array $layout
     *
     * @return array
     */
    private function normalizeLayout(array $layout): array
    {
        $base = $this->getDefaultLayout();
        $layout = $this->mergeLayout($base, $layout);

        $layout['type'] = 'design';
        $layout['columns'] = 12;
        $layout['preset'] = $this->trimText($layout['preset'] ?? $base['preset'], 40, $base['preset']);
        $layout['theme'] = $this->enum((string)($layout['theme'] ?? ''), ['light', 'dark', 'corporate', 'soft', 'emerald', 'mono', 'rexpress'], $base['theme']);
        $layout['width'] = $this->width($layout['width'] ?? $base['width'], $base['width']);
        $layout['density'] = $this->enum((string)($layout['density'] ?? ''), ['compact', 'normal', 'comfortable'], $base['density']);

        $layout['colors']['primary'] = $this->hex($layout['colors']['primary'] ?? '', $base['colors']['primary']);
        $layout['colors']['background'] = $this->hex($layout['colors']['background'] ?? '', $base['colors']['background']);
        $layout['colors']['card'] = $this->hex($layout['colors']['card'] ?? '', $base['colors']['card']);
        $layout['colors']['text'] = $this->hex($layout['colors']['text'] ?? '', $base['colors']['text']);

        $layout['typography']['font'] = $this->enum((string)($layout['typography']['font'] ?? ''), ['system', 'arial', 'georgia', 'inter', 'onest'], $base['typography']['font']);
        $layout['typography']['size'] = $this->intString($layout['typography']['size'] ?? '', 13, 20, $base['typography']['size']);

        $layout['shape']['radius'] = $this->intString($layout['shape']['radius'] ?? '', 0, 40, $base['shape']['radius']);
        $layout['shape']['fieldRadius'] = $this->intString($layout['shape']['fieldRadius'] ?? '', 0, 24, $base['shape']['fieldRadius']);

        $layout['surface']['fieldStyle'] = $this->enum((string)($layout['surface']['fieldStyle'] ?? ''), ['outline', 'filled', 'underline'], $base['surface']['fieldStyle']);
        $layout['surface']['shadow'] = $this->enum((string)($layout['surface']['shadow'] ?? ''), ['none', 'soft', 'deep'], $base['surface']['shadow']);
        $layout['surface']['border'] = $this->enum((string)($layout['surface']['border'] ?? ''), ['none', 'subtle', 'accent'], $base['surface']['border']);

        $layout['container']['align'] = $this->enum((string)($layout['container']['align'] ?? ''), ['left', 'center', 'right'], $base['container']['align']);
        $layout['container']['padding'] = $this->enum((string)($layout['container']['padding'] ?? ''), ['compact', 'normal', 'wide'], $base['container']['padding']);
        $layout['container']['backgroundImageUrl'] = $this->publicUrl($layout['container']['backgroundImageUrl'] ?? '');
        $layout['container']['backgroundMode'] = $this->enum((string)($layout['container']['backgroundMode'] ?? ''), ['cover', 'contain', 'repeat'], $base['container']['backgroundMode']);
        $layout['container']['backgroundOverlay'] = $this->enum((string)($layout['container']['backgroundOverlay'] ?? ''), ['none', 'light', 'dark'], $base['container']['backgroundOverlay']);

        $layout['media']['icon'] = $this->trimText($layout['media']['icon'] ?? $base['media']['icon'], 8, $base['media']['icon']);
        $layout['media']['imageUrl'] = $this->publicUrl($layout['media']['imageUrl'] ?? '');
        $layout['media']['imageMode'] = $this->enum((string)($layout['media']['imageMode'] ?? ''), ['cover', 'contain'], $base['media']['imageMode']);
        $layout['media']['imageHeight'] = $this->intString($layout['media']['imageHeight'] ?? '', 80, 360, $base['media']['imageHeight']);

        $layout['icons']['enabled'] = $this->bool($layout['icons']['enabled'] ?? true);
        $layout['icons']['formEnabled'] = $this->bool($layout['icons']['formEnabled'] ?? true);
        $layout['icons']['noteEnabled'] = $this->bool($layout['icons']['noteEnabled'] ?? true);
        $layout['icons']['successEnabled'] = $this->bool($layout['icons']['successEnabled'] ?? true);
        $layout['icons']['shape'] = $this->enum((string)($layout['icons']['shape'] ?? ''), ['none', 'circle', 'rounded', 'square'], $base['icons']['shape']);
        $layout['icons']['background'] = $this->hex($layout['icons']['background'] ?? '', $base['icons']['background']);
        $layout['icons']['color'] = $this->hex($layout['icons']['color'] ?? '', $base['icons']['color']);
        $layout['icons']['size'] = $this->enum((string)($layout['icons']['size'] ?? ''), ['sm', 'md', 'lg', 'xl'], $base['icons']['size']);

        $layout['submitButton']['style'] = $this->enum((string)($layout['submitButton']['style'] ?? ''), ['crm', 'primary', 'outline'], $base['submitButton']['style']);
        $layout['submitButton']['align'] = $this->enum((string)($layout['submitButton']['align'] ?? ''), ['left', 'center', 'right', 'stretch'], $base['submitButton']['align']);
        $layout['submitButton']['textColor'] = $this->enum((string)($layout['submitButton']['textColor'] ?? ''), ['dark', 'light'], $base['submitButton']['textColor']);
        $layout['submitButton']['size'] = $this->enum((string)($layout['submitButton']['size'] ?? ''), ['sm', 'default', 'lg'], $base['submitButton']['size']);

        $layout['sidebarNote']['enabled'] = $this->bool($layout['sidebarNote']['enabled'] ?? false);
        $layout['sidebarNote']['title'] = $this->trimText($layout['sidebarNote']['title'] ?? $base['sidebarNote']['title'], 80, $base['sidebarNote']['title']);
        $layout['sidebarNote']['text'] = $this->trimText($layout['sidebarNote']['text'] ?? '', 600, '');
        $layout['sidebarNote']['placement'] = $this->enum((string)($layout['sidebarNote']['placement'] ?? ($layout['sidebarNote']['position'] ?? '')), ['top', 'right', 'bottom', 'left'], $base['sidebarNote']['placement'] ?? 'right');
        $layout['sidebarNote']['position'] = $this->enum((string)($layout['sidebarNote']['position'] ?? ($layout['sidebarNote']['placement'] ?? '')), ['left', 'right'], $base['sidebarNote']['position']);
        $layout['sidebarNote']['variant'] = $this->enum((string)($layout['sidebarNote']['variant'] ?? ''), ['info', 'warning', 'success', 'danger'], $base['sidebarNote']['variant']);
        $layout['sidebarNote']['icon'] = $this->trimText($layout['sidebarNote']['icon'] ?? $base['sidebarNote']['icon'], 8, $base['sidebarNote']['icon']);
        $layout['sidebarNote']['width'] = $this->intString($layout['sidebarNote']['width'] ?? '', 160, 360, $base['sidebarNote']['width']);
        $layout['sidebarNotes'] = $this->normalizeSidebarNotes(
            is_array($layout['sidebarNotes'] ?? null) ? $layout['sidebarNotes'] : [],
            $layout['sidebarNote']
        );
        if (!empty($layout['sidebarNotes'][0])) {
            $layout['sidebarNote'] = array_merge($layout['sidebarNote'], $layout['sidebarNotes'][0]);
        }

        $layout['responsive']['enabled'] = $this->bool($layout['responsive']['enabled'] ?? true);
        $layout['responsive']['breakpoint'] = $this->enum((string)($layout['responsive']['breakpoint'] ?? ''), ['480', '640', '760'], $base['responsive']['breakpoint']);
        $layout['responsive']['fullWidth'] = $this->bool($layout['responsive']['fullWidth'] ?? true);
        $layout['responsive']['compact'] = $this->bool($layout['responsive']['compact'] ?? true);
        $layout['responsive']['sideNoteMobile'] = $this->enum((string)($layout['responsive']['sideNoteMobile'] ?? ''), ['top', 'bottom', 'hide'], $base['responsive']['sideNoteMobile']);
        $layout['responsive']['imageMobile'] = $this->enum((string)($layout['responsive']['imageMobile'] ?? ''), ['show', 'hide'], $base['responsive']['imageMobile']);

        $mode = (string)($layout['postSubmit']['mode'] ?? 'message');
        if ($mode === 'hideForm') {
            $mode = 'hide_form';
        }
        $layout['postSubmit']['mode'] = $this->enum($mode, ['message', 'hide_form', 'redirect', 'reload'], 'message');
        $layout['postSubmit']['title'] = $this->trimText($layout['postSubmit']['title'] ?? $base['postSubmit']['title'], 90, $base['postSubmit']['title']);
        $layout['postSubmit']['icon'] = $this->trimText($layout['postSubmit']['icon'] ?? $base['postSubmit']['icon'], 8, $base['postSubmit']['icon']);
        $layout['postSubmit']['imageUrl'] = $this->publicUrl($layout['postSubmit']['imageUrl'] ?? '');
        $layout['postSubmit']['message'] = $this->trimText($layout['postSubmit']['message'] ?? $base['postSubmit']['message'], 800, $base['postSubmit']['message']);
        $layout['postSubmit']['buttonText'] = $this->trimText($layout['postSubmit']['buttonText'] ?? '', 80, '');
        $layout['postSubmit']['redirectUrl'] = $this->publicUrl($layout['postSubmit']['redirectUrl'] ?? '');
        $layout['postSubmit']['showAgainButton'] = $this->bool($layout['postSubmit']['showAgainButton'] ?? false);

        $layout['advanced']['cssClass'] = $this->cssClassList($layout['advanced']['cssClass'] ?? '');
        $layout['advanced']['animation'] = $this->enum((string)($layout['advanced']['animation'] ?? ''), ['none', 'fade', 'slide'], $base['advanced']['animation']);

        return $layout;
    }

    /**
     * Нормализует sidebar notes.
     *
     * @param array $notes
     * @param array $fallbackNote
     *
     * @return array
     */
    private function normalizeSidebarNotes(array $notes, array $fallbackNote): array
    {
        if (empty($notes) && (!empty($fallbackNote['enabled']) || trim((string)($fallbackNote['text'] ?? '')) !== '')) {
            $notes = [$fallbackNote];
        }

        $result = [];
        foreach (array_slice($notes, 0, 4) as $index => $note) {
            if (!is_array($note)) {
                continue;
            }

            $result[] = [
                'uid' => $this->trimText($note['uid'] ?? ('note_' . ($index + 1)), 40, 'note_' . ($index + 1)),
                'enabled' => $this->bool($note['enabled'] ?? false),
                'title' => $this->trimText($note['title'] ?? ($index === 0 ? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_001') : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_002')), 80, $index === 0 ? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_003') : \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_004')),
                'text' => $this->trimText($note['text'] ?? '', 600, ''),
                'placement' => $this->enum((string)($note['placement'] ?? ($note['position'] ?? 'right')), ['top', 'right', 'bottom', 'left'], 'right'),
                'position' => $this->enum((string)($note['position'] ?? ($note['placement'] ?? 'right')), ['left', 'right'], 'right'),
                'variant' => $this->enum((string)($note['variant'] ?? ''), ['info', 'warning', 'success', 'danger'], 'info'),
                'icon' => $this->trimText($note['icon'] ?? 'i', 8, 'i'),
                'width' => $this->intString($note['width'] ?? '', 160, 360, '220'),
            ];
        }

        return $result;
    }

    /**
     * Возвращает default layout.
     *
     * @return array
     */
    private function getDefaultLayout(): array
    {
        return [
            'type' => 'design',
            'columns' => 12,
            'preset' => 'classic',
            'theme' => 'light',
            'width' => '760',
            'density' => 'normal',
            'colors' => ['primary' => '#2fc6f6', 'background' => '#f5f7fb', 'card' => '#ffffff', 'text' => '#172b4d'],
            'typography' => ['font' => 'system', 'size' => '15'],
            'shape' => ['radius' => '14', 'fieldRadius' => '10'],
            'surface' => ['fieldStyle' => 'outline', 'shadow' => 'soft', 'border' => 'subtle'],
            'container' => ['align' => 'center', 'padding' => 'normal', 'backgroundImageUrl' => '', 'backgroundMode' => 'cover', 'backgroundOverlay' => 'light'],
            'media' => ['icon' => 'i', 'imageUrl' => '', 'imageMode' => 'cover', 'imageHeight' => '140'],
            'icons' => ['enabled' => true, 'formEnabled' => true, 'noteEnabled' => true, 'successEnabled' => true, 'shape' => 'rounded', 'background' => '#e8f7ff', 'color' => '#172b4d', 'size' => 'md'],
            'submitButton' => ['style' => 'crm', 'align' => 'left', 'textColor' => 'dark', 'size' => 'default'],
            'sidebarNote' => ['enabled' => false, 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_005'), 'text' => '', 'placement' => 'right', 'position' => 'right', 'variant' => 'info', 'icon' => 'i', 'width' => '220'],
            'sidebarNotes' => [],
            'responsive' => ['enabled' => true, 'breakpoint' => '760', 'fullWidth' => true, 'compact' => true, 'sideNoteMobile' => 'top', 'imageMobile' => 'show'],
            'postSubmit' => ['mode' => 'message', 'title' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_006'), 'icon' => '✓', 'imageUrl' => '', 'message' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_007'), 'buttonText' => '', 'redirectUrl' => '', 'showAgainButton' => false],
            'advanced' => ['cssClass' => '', 'animation' => 'none'],
        ];
    }

    /**
     * Объединяет настройки оформления с настройками по умолчанию.
     *
     * @param array $base
     * @param array $layout
     *
     * @return array
     */
    private function mergeLayout(array $base, array $layout): array
    {
        foreach ($layout as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeLayout($base[$key], $value);
                continue;
            }
            $base[$key] = $value;
        }

        return $base;
    }

    /**

     * Нормализует HEX-цвет.

     *

     * @param mixed $value

     * @param string $fallback

     *

     * @return string

     */

    private function hex(mixed $value, string $fallback): string
    {
        $value = trim((string)$value);
        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : $fallback;
    }

    /**
     * Нормализует значение по разрешённому списку.
     *
     * @param string $value
     * @param array $allowed
     * @param string $fallback
     *
     * @return string
     */
    private function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**

     * Нормализует значение ширины формы.

     *

     * @param mixed $value

     * @param string $fallback

     *

     * @return string

     */

    private function width(mixed $value, string $fallback): string
    {
        $value = trim((string)$value);
        if ($value === '100%') {
            return $value;
        }

        return $this->intString($value, 320, 1440, $fallback);
    }

    /**

     * Нормализует числовую строку.

     *

     * @param mixed $value

     * @param int $min

     * @param int $max

     * @param string $fallback

     *

     * @return string

     */

    private function intString(mixed $value, int $min, int $max, string $fallback): string
    {
        $number = (int)preg_replace('/[^0-9-]/', '', (string)$value);
        if ($number === 0 && !preg_match('/0/', (string)$value)) {
            $number = (int)$fallback;
        }
        $number = max($min, min($max, $number));
        return (string)$number;
    }

    /**

     * Нормализует булевое значение.

     *

     * @param mixed $value

     *

     * @return bool

     */

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string)$value), ['1', 'y', 'yes', 'true'], true);
    }

    /**

     * Обрезает текст до допустимой длины.

     *

     * @param mixed $value

     * @param int $maxLength

     * @param string $fallback

     *

     * @return string

     */

    private function trimText(mixed $value, int $maxLength, string $fallback): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $fallback;
        }
        return mb_substr($value, 0, $maxLength);
    }

    /**

     * Нормализует публичный URL ресурса.

     *

     * @param mixed $value

     *

     * @return string

     */

    private function publicUrl(mixed $value): string
    {
        $value = trim((string)$value);
        if ($value === '' || preg_match('/[\x00-\x1F<>"\']/', $value) || preg_match('/^(javascript|data|vbscript):/i', $value)) {
            return '';
        }
        if (preg_match('/^https:\/\/[^\s]+$/i', $value) || preg_match('/^\/(?!\/)[^\s]*$/', $value)) {
            return $value;
        }
        return '';
    }

    /**

     * Нормализует список CSS-классов.

     *

     * @param mixed $value

     *

     * @return string

     */

    private function cssClassList(mixed $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_ -]/', '', (string)$value) ?? '';
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $parts = array_slice(array_values(array_filter($parts)), 0, 6);
        return implode(' ', $parts);
    }

    /**
     * Нормализует полей.
     *
     * @param array $fields
     *
     * @return array
     */
    private function normalizeFields(array $fields): array
    {
        $result = [];
        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            if (($field['type'] ?? '') === 'hl_relation') {
                $field['type'] = 'string';
                if (is_array($field['settings'] ?? null)) {
                    unset(
                        $field['settings']['sourceHlBlockId'],
                        $field['settings']['sourceFieldId'],
                        $field['settings']['display']
                    );
                }
            }

            $result[] = $this->fieldTypeRegistry->normalizeField($field, $index);
        }

        usort($result, static fn(array $a, array $b): int => ((int)($a['sort'] ?? 0)) <=> ((int)($b['sort'] ?? 0)));

        return array_values($result);
    }

    /**
     * Нормализует sections.
     *
     * @param array $sections
     *
     * @return array
     */
    private function normalizeSections(array $sections): array
    {
        $result = [];
        foreach ($sections as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            $uid = trim((string)($section['uid'] ?? $section['id'] ?? '')) ?: 'section_' . ($index + 1);
            $result[] = [
                'uid' => $uid,
                'id' => $uid,
                'title' => trim((string)($section['title'] ?? $section['name'] ?? '')) ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMTARGETRESOLVERSERVICE_008'),
                'description' => trim((string)($section['description'] ?? '')),
                'sort' => (int)($section['sort'] ?? (($index + 1) * 100)),
            ];
        }

        usort($result, static fn(array $a, array $b): int => ((int)($a['sort'] ?? 0)) <=> ((int)($b['sort'] ?? 0)));
        return array_values($result);
    }
}
