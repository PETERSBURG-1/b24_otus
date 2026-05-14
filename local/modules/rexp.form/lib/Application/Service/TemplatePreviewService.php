<?php

namespace Rexp\Form\Application\Service;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис подготовки предпросмотра шаблона формы.
 */
final class TemplatePreviewService
{
    /**
     * Дополняет шаблоны данными для предпросмотра.
     *
     * @param array $templates
     *
     * @return array
     */
    public function enrichTemplates(array $templates): array
    {
        foreach ($templates as &$template) {
            $schema = is_array($template['schema'] ?? null) ? $template['schema'] : [];
            $template['previewLayout'] = $this->buildPreviewLayout($schema);
        }
        unset($template);

        return $templates;
    }

    /**
     * Формирует preview layout.
     *
     * @param array $schema
     *
     * @return array
     */
    private function buildPreviewLayout(array $schema): array
    {
        $sections = is_array($schema['sections'] ?? null) ? $schema['sections'] : [];
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $result = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $sectionId = (string)($section['uid'] ?? '');
            $result[] = [
                'title' => (string)($section['title'] ?? ''),
                'items' => array_values(array_map(
                    static function (array $field): array {
                        return [
                            'label' => (string)($field['label'] ?? $field['code'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_TEMPLATEPREVIEWSERVICE_001')),
                            'type' => (string)($field['type'] ?? 'string'),
                            'width' => (int)($field['width'] ?? 12),
                        ];
                    },
                    array_filter($fields, static fn(array $field): bool => (string)($field['sectionId'] ?? '') === $sectionId && (string)($field['type'] ?? '') !== 'hidden')
                )),
            ];
        }

        return $result;
    }
}
