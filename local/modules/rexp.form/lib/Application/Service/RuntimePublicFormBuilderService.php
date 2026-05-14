<?php

namespace Rexp\Form\Application\Service;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис сборки публичного представления формы.
 */
final class RuntimePublicFormBuilderService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormTargetResolverService $targetResolver
     * @param RuntimeDirectoryService $directoryService
     * @param RuntimeValueNormalizerService $normalizer
     * @param RuntimeConditionEvaluatorService $conditionEvaluator
     * @param RuntimeRuleEvaluatorService $ruleEvaluator
     */
    public function __construct(
        private readonly FormTargetResolverService $targetResolver,
        private readonly RuntimeDirectoryService $directoryService,
        private readonly RuntimeValueNormalizerService $normalizer,
        private readonly RuntimeConditionEvaluatorService $conditionEvaluator,
        private readonly RuntimeRuleEvaluatorService $ruleEvaluator,
    ) {
    }

    /**
     * Формирует данные.
     *
     * @param array $row
     * @param int $entryId
     * @param string $mode
     *
     * @return array
     */
    public function build(array $row, int $entryId = 0, string $mode = 'create'): array
    {
        $schema = $this->targetResolver->normalizeSchema($this->decodeSchema((string)($row['SCHEMA'] ?? '')));
        $schema = $this->normalizeSchemaForRuntime($schema);
        $initialData = $this->buildInitialData($schema);

        return [
            'id' => (int)($row['ID'] ?? 0),
            'code' => (string)($row['CODE'] ?? ''),
            'name' => (string)($row['NAME'] ?? ''),
            'mode' => 'create',
            'entryId' => 0,
            'schema' => $schema,
            'initialValues' => $initialData['values'],
            'fileInfo' => $initialData['fileInfo'],
        ];
    }

    /**
     * Декодирует схемы.
     *
     * @param string $schemaJson
     *
     * @return array
     */
    private function decodeSchema(string $schemaJson): array
    {
        $decoded = json_decode($schemaJson, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'version' => 6,
            'layout' => ['type' => 'grid', 'columns' => 12],
            'settings' => [
                'submitText' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEPUBLICFORMBUILDERSERVICE_001'),
                'successText' => \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEPUBLICFORMBUILDERSERVICE_002'),
                'stateSettings' => $this->normalizeStateSettings([]),
            ],
            'sections' => [],
            'fields' => [],
            'rules' => [],
        ];
    }

    /**
     * Нормализует схемы for публичной формы.
     *
     * @param array $schema
     *
     * @return array
     */
    private function normalizeSchemaForRuntime(array $schema): array
    {
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $sections = is_array($schema['sections'] ?? null) ? $schema['sections'] : [];
        $settings = is_array($schema['settings'] ?? null) ? $schema['settings'] : [];

        $normalizedSections = $this->normalizeSections($sections);
        $defaultSectionId = (string)($normalizedSections[0]['uid'] ?? '');
        $sectionIds = array_column($normalizedSections, 'uid');
        $employeeItems = null;
        $departmentItems = null;
        $runtimeFields = [];

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            $type = trim((string)($field['type'] ?? 'string')) ?: 'string';
            $legacyType = trim((string)($field['legacyType'] ?? ''));
            $items = is_array($field['items'] ?? null) ? $field['items'] : [];
            $source = is_array($field['source'] ?? null) ? $field['source'] : [];
            $selectorEntities = is_array($field['selectorEntities'] ?? null) ? $field['selectorEntities'] : (is_array($source['entities'] ?? null) ? $source['entities'] : []);
            $view = is_array($field['ui'] ?? null) ? trim((string)($field['ui']['view'] ?? '')) : '';
            $isEntitySelectorView = in_array($type, ['entity_selector', 'user', 'department'], true)
                || ($type === 'enumeration' && $view === 'entity_selector');
            $isUserSelector = $type === 'user'
                || ($isEntitySelectorView && (in_array('user', $selectorEntities, true) || in_array($legacyType, ['user', 'employee'], true)));
            $isDepartmentSelector = $type === 'department'
                || ($isEntitySelectorView && (in_array('department', $selectorEntities, true) || $legacyType === 'department' || ($field['relationType'] ?? '') === 'department'));

            if ($isUserSelector && empty($items)) {
                $employeeItems ??= $this->directoryService->getEmployeeItems();
                $items = $employeeItems;
                $field['selectorEntities'] = $selectorEntities !== [] ? $selectorEntities : ['user'];
                $field['relationType'] = trim((string)($field['relationType'] ?? '')) ?: 'user';
            }

            if ($isDepartmentSelector) {
                $departmentItems ??= $this->directoryService->getDepartmentItems();
                $items = $departmentItems;
                $field['relationType'] = 'department';
                $field['selectorEntities'] = ['department'];
                $field['ui'] = is_array($field['ui'] ?? null) ? $field['ui'] : [];
                $field['ui']['view'] = 'department_select';
                $source = is_array($field['source'] ?? null) ? $field['source'] : [];
                $source['provider'] = 'iblock.department';
                $source['context'] = trim((string)($source['context'] ?? 'REXP_FORM')) ?: 'REXP_FORM';
                $source['entities'] = ['department'];
                $field['source'] = $source;
            } elseif ($isEntitySelectorView && $selectorEntities !== []) {
                $field['selectorEntities'] = $selectorEntities;
                $field['relationType'] = trim((string)($field['relationType'] ?? '')) ?: (string)reset($selectorEntities);
            }

            $field['uid'] = trim((string)($field['uid'] ?? '')) ?: ('field_' . ($index + 1));
            $field['sectionId'] = trim((string)($field['sectionId'] ?? $field['sectionUid'] ?? $defaultSectionId));
            if (!in_array($field['sectionId'], $sectionIds, true)) {
                $field['sectionId'] = $defaultSectionId;
            }
            $field['sectionUid'] = $field['sectionId'];
            $field['width'] = (int)($field['width'] ?? 12);
            if (!in_array($field['width'], [12, 6, 4], true)) {
                $field['width'] = 12;
            }
            $view = is_array($field['ui'] ?? null) ? trim((string)($field['ui']['view'] ?? '')) : '';
            $field['multiple'] = $this->normalizer->resolveFieldMultiple($type, $field['multiple'] ?? null)
                || ($type === 'enumeration' && in_array($view, ['checkbox', 'checkboxes', 'tag_input'], true))
                || $type === 'checkbox_list';
            $field['items'] = $this->normalizeFieldItems($items);
            $field['condition'] = $this->conditionEvaluator->normalizeFieldCondition($field['condition'] ?? []);
            $field['dynamicOptions'] = is_array($field['dynamicOptions'] ?? null) ? $field['dynamicOptions'] : [];
            $field['runtime'] = [
                'hasBinding' => false,
                'bindingFieldName' => '',
                'bindingSource' => '',
            ];
            $runtimeFields[] = $field;
        }

        $schema['version'] = 6;
        $schema['layout'] = is_array($schema['layout'] ?? null)
            ? ($schema['layout'] + ['type' => 'grid', 'columns' => 12])
            : ['type' => 'grid', 'columns' => 12];
        $schema['sections'] = $normalizedSections;
        $schema['fields'] = $runtimeFields;
        $schema['settings'] = [
            'submitText' => trim((string)($settings['submitText'] ?? '')) ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEPUBLICFORMBUILDERSERVICE_003'),
            'successText' => trim((string)($settings['successText'] ?? '')) ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEPUBLICFORMBUILDERSERVICE_004'),
            'appearance' => $this->normalizeAppearanceSettings($settings['appearance'] ?? []),
            'stateSettings' => $this->normalizeStateSettings($settings['stateSettings'] ?? []),
            'submitProcessing' => is_array($settings['submitProcessing'] ?? null) ? $settings['submitProcessing'] : ['logSubmissions' => true],
        ];
        $schema['rules'] = $this->ruleEvaluator->normalizeRules($schema);

        return $schema;
    }

    /**
     * Нормализует поля элементов.
     *
     * @param array $items
     *
     * @return array
     */
    private function normalizeFieldItems(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $value = isset($item['value']) ? (string)$item['value'] : (isset($item['id']) ? (string)$item['id'] : '');
                $label = isset($item['label']) ? (string)$item['label'] : (isset($item['title']) ? (string)$item['title'] : (isset($item['text']) ? (string)$item['text'] : $value));
                $normalized = $item;
                if (!isset($normalized['value']) && $value !== '') {
                    $normalized['value'] = $value;
                }
                if (!isset($normalized['label'])) {
                    $normalized['label'] = $label;
                }
                if (!isset($normalized['title'])) {
                    $normalized['title'] = $label;
                }
                if ($value !== '') {
                    $result[] = $normalized;
                }
                continue;
            }

            if ($item === null || $item === '') {
                continue;
            }
            $value = (string)$item;
            $result[] = ['value' => $value, 'label' => $value, 'title' => $value];
        }

        return $result;
    }

    /**
     * Формирует initial data.
     *
     * @param array $schema
     *
     * @return array
     */
    private function buildInitialData(array $schema): array
    {
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $values = [];
        $fileInfo = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldCode = trim((string)($field['code'] ?? ''));
            if ($fieldCode === '') {
                continue;
            }

            $fieldType = trim((string)($field['type'] ?? 'string')) ?: 'string';
            if ($fieldType === 'file') {
                $fileInfo[$fieldCode] = $this->normalizeExistingFileInfo(
                    null,
                    $this->normalizer->resolveFieldMultiple($fieldType, $field['multiple'] ?? null)
                );
                continue;
            }

            if (in_array($fieldType, ['checkbox', 'switch', 'boolean'], true)) {
                $values[$fieldCode] = !empty($field['defaultValue']);
                continue;
            }

            $view = is_array($field['ui'] ?? null) ? trim((string)($field['ui']['view'] ?? '')) : '';
            if ($this->normalizer->resolveFieldMultiple($fieldType, $field['multiple'] ?? null)
                || ($fieldType === 'enumeration' && in_array($view, ['checkbox', 'checkboxes', 'tag_input'], true))
                || $fieldType === 'checkbox_list') {
                $values[$fieldCode] = is_array($field['defaultValue'] ?? null) ? $field['defaultValue'] : [];
                continue;
            }

            $values[$fieldCode] = $field['defaultValue'] ?? '';
        }

        return ['values' => $values, 'fileInfo' => $fileInfo];
    }

    /**
     * Нормализует existing файла info.
     *
     * @param mixed $fileValue
     * @param bool $multiple
     *
     * @return array
     */
    private function normalizeExistingFileInfo(mixed $fileValue, bool $multiple): array
    {
        $fileIds = [];
        $rawList = is_array($fileValue) ? $fileValue : ($fileValue ? [$fileValue] : []);
        foreach ($rawList as $raw) {
            $fileId = (int)$raw;
            if ($fileId <= 0) {
                continue;
            }
            $file = \CFile::GetFileArray($fileId);
            if (!is_array($file)) {
                continue;
            }
            $fileIds[] = [
                'id' => $fileId,
                'name' => (string)($file['ORIGINAL_NAME'] ?? $file['FILE_NAME'] ?? (\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEPUBLICFORMBUILDERSERVICE_005') . $fileId)),
                'size' => (int)($file['FILE_SIZE'] ?? 0),
                'url' => (string)($file['SRC'] ?? ''),
            ];
        }

        return [
            'multiple' => $multiple,
            'files' => $multiple ? $fileIds : array_slice($fileIds, 0, 1),
        ];
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
            $uid = trim((string)($section['uid'] ?? $section['id'] ?? '')) ?: ('section_' . ($index + 1));
            $result[] = [
                'uid' => $uid,
                'id' => $uid,
                'title' => trim((string)($section['title'] ?? $section['name'] ?? '')) ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEPUBLICFORMBUILDERSERVICE_006'),
                'description' => trim((string)($section['description'] ?? '')),
                'sort' => (int)($section['sort'] ?? (($index + 1) * 100)),
            ];
        }

        if ($result === []) {
        }

        usort($result, static fn(array $a, array $b): int => ((int)$a['sort']) <=> ((int)$b['sort']));
        return array_values($result);
    }

    /**
     * Нормализует appearance настроек.
     *
     * @param mixed $settings
     *
     * @return array
     */
    private function normalizeAppearanceSettings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];
        return [
            'theme' => trim((string)($settings['theme'] ?? 'light')) ?: 'light',
            'layout' => trim((string)($settings['layout'] ?? 'card')) ?: 'card',
        ];
    }

    /**
     * Нормализует state настроек.
     *
     * @param mixed $settings
     *
     * @return array
     */
    private function normalizeStateSettings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];
        return [
            'success' => is_array($settings['success'] ?? null) ? $settings['success'] : ['mode' => 'message'],
            'error' => is_array($settings['error'] ?? null) ? $settings['error'] : ['mode' => 'message'],
        ];
    }
}
