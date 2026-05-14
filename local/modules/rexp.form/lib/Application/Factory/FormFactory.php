<?php

namespace Rexp\Form\Application\Factory;

use Rexp\Form\Domain\Entity\Form;

/**
 * Фабрика доменной сущности формы.
 */
final class FormFactory
{
    /**
     * Создаёт доменную сущность формы из строки БД.
     *
     * @param array $row
     *
     * @return Form
     */
    public function createFromRow(array $row): Form
    {
        $schema = [];
        $schemaRaw = (string)($row['SCHEMA'] ?? $row['FORM_SCHEMA'] ?? '');
        if ($schemaRaw !== '') {
            $decoded = json_decode($schemaRaw, true);
            if (is_array($decoded)) {
                $schema = $decoded;
            }
        }

        return new Form(
            (int)($row['ID'] ?? 0),
            (string)($row['NAME'] ?? ''),
            (string)($row['CODE'] ?? ''),
            (string)($row['ACTIVE'] ?? 'N') === 'Y',
            (string)($row['ARCHIVED'] ?? 'N') === 'Y',
            $schema,
            (int)($row['CREATED_BY'] ?? 0),
            (int)($row['UPDATED_BY'] ?? 0),
            (string)($row['CREATED_AT'] ?? ''),
            (string)($row['UPDATED_AT'] ?? ''),
        );
    }
}
