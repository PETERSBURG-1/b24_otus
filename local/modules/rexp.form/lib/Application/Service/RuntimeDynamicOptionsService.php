<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;
use RuntimeException;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис загрузки динамических вариантов полей.
 */
final class RuntimeDynamicOptionsService
{
    /**
     * Возвращает элементов.
     *
     * @param string $provider
     * @param mixed $sourceValue
     * @param array $config
     *
     * @return array
     */
    public function getItems(string $provider, mixed $sourceValue, array $config = []): array
    {
        $provider = trim($provider);
        return match ($provider) {
            'work_positions_by_department', 'department_positions' => $this->getWorkPositionsByDepartment($sourceValue, $config),
            default => [],
        };
    }

    /**
     * Возвращает work positions по отдела.
     *
     * @param mixed $sourceValue
     * @param array $config
     *
     * @return array
     */
    private function getWorkPositionsByDepartment(mixed $sourceValue, array $config = []): array
    {
        $departmentId = $this->extractScalarValue($sourceValue);
        if ($departmentId === '') {
            return [];
        }

        if (!Loader::includeModule('highloadblock')) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEDYNAMICOPTIONSSERVICE_001'));
        }

        $hlName = trim((string)($config['hlName'] ?? 'WorkPositions')) ?: 'WorkPositions';
        $departmentField = trim((string)($config['departmentField'] ?? 'UF_DEPART_XML_ID')) ?: 'UF_DEPART_XML_ID';
        $titleField = trim((string)($config['titleField'] ?? 'UF_NAME')) ?: 'UF_NAME';

        $hlBlock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $hlName],
            'limit' => 1,
        ])->fetch();

        if (!is_array($hlBlock) || (int)($hlBlock['ID'] ?? 0) <= 0) {
            return [];
        }

        $entity = HL\HighloadBlockTable::compileEntity($hlBlock);
        $dataClass = $entity->getDataClass();

        $rows = $dataClass::query()
            ->setSelect(['ID', $titleField])
            ->where($departmentField, '=', $departmentId)
            ->setOrder([$titleField => 'ASC', 'ID' => 'ASC'])
            ->exec();

        $result = [];
        while ($row = $rows->fetch()) {
            $id = (string)($row['ID'] ?? '');
            if ($id === '') {
                continue;
            }
            $title = trim((string)($row[$titleField] ?? ''));
            $result[] = [
                'value' => $id,
                'label' => $title !== '' ? sprintf('%s [%s]', $title, $id) : $id,
                'xmlId' => $id,
            ];
        }

        return $result;
    }

    /**
     * Извлекает scalar значения.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function extractScalarValue(mixed $value): string
    {
        if (is_array($value)) {
            if (array_key_exists('id', $value) || array_key_exists('value', $value)) {
                return trim((string)($value['id'] ?? $value['value'] ?? ''));
            }
            foreach ($value as $item) {
                $normalized = $this->extractScalarValue($item);
                if ($normalized !== '') {
                    return $normalized;
                }
            }
            return '';
        }

        if ($value === null) {
            return '';
        }

        return trim((string)$value);
    }
}
