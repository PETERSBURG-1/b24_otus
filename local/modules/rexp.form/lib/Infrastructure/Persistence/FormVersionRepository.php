<?php

namespace Rexp\Form\Infrastructure\Persistence;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Rexp\Form\Domain\Entity\Form;
use Rexp\Form\Infrastructure\Persistence\Orm\FormVersionTable;

/**
 * Репозиторий версий форм.
 */
final class FormVersionRepository
{
    /**
     * Проверяет существование таблицы версий.
     *
     * @return bool
     */
    public function existsTable(): bool
    {
        return Application::getConnection()->isTableExists($this->getTableName());
    }

    /**
     * Создаёт snapshot.
     *
     * @param Form $form
     * @param int $userId
     * @param string $type
     *
     * @return void
     */
    public function createSnapshot(Form $form, int $userId, string $type = 'save'): void
    {
        if (!$this->existsTable()) {
            return;
        }

        $result = FormVersionTable::add([
            'FORM_ID' => $form->getId(),
            'VERSION_TYPE' => $type,
            'NAME' => $form->getName(),
            'CODE' => $form->getCode(),
            'STATUS' => $form->isActive() ? 'published' : 'draft',
            'SCHEMA' => json_encode($form->getSchema(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            'CREATED_BY' => $userId,
            'CREATED_AT' => new DateTime(),
        ]);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }

    /**
     * Возвращает список по формы ID.
     *
     * @param int $formId
     *
     * @return array
     */
    public function listByFormId(int $formId): array
    {
        if (!$this->existsTable()) {
            return [];
        }

        $result = FormVersionTable::getList([
            'filter' => ['=FORM_ID' => $formId],
            'order' => ['ID' => 'DESC'],
            'select' => ['ID', 'FORM_ID', 'VERSION_TYPE', 'NAME', 'CODE', 'STATUS', 'CREATED_BY', 'CREATED_AT'],
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $items[] = [
                'id' => (int)$row['ID'],
                'formId' => (int)$row['FORM_ID'],
                'type' => (string)$row['VERSION_TYPE'],
                'name' => (string)$row['NAME'],
                'code' => (string)$row['CODE'],
                'status' => (string)$row['STATUS'],
                'createdBy' => (int)$row['CREATED_BY'],
                'createdAt' => (string)$row['CREATED_AT'],
                'versionNumber' => 0,
            ];
        }

        return $items;
    }


    /**
     * Возвращает полный список записей.
     *
     * @return array
     */
    public function listAll(): array
    {
        if (!$this->existsTable()) {
            return [];
        }

        $result = FormVersionTable::getList([
            'order' => ['ID' => 'DESC'],
            'select' => ['ID', 'FORM_ID', 'VERSION_TYPE', 'NAME', 'CODE', 'STATUS', 'CREATED_BY', 'CREATED_AT'],
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $items[] = [
                'id' => (int)$row['ID'],
                'formId' => (int)$row['FORM_ID'],
                'type' => (string)$row['VERSION_TYPE'],
                'name' => (string)$row['NAME'],
                'code' => (string)$row['CODE'],
                'status' => (string)$row['STATUS'],
                'createdBy' => (int)$row['CREATED_BY'],
                'createdAt' => (string)$row['CREATED_AT'],
                'versionNumber' => 0,
            ];
        }

        return $items;
    }

    /**
     * Возвращает snapshot.
     *
     * @param int $versionId
     *
     * @return ?array
     */
    public function getSnapshot(int $versionId): ?array
    {
        if (!$this->existsTable() || $versionId <= 0) {
            return null;
        }

        $row = FormVersionTable::getByPrimary($versionId)->fetch();
        if (!$row) {
            return null;
        }

        $schema = [];
        $schemaRaw = (string)($row['SCHEMA'] ?? $row['VERSION_SCHEMA'] ?? '');
        if ($schemaRaw !== '') {
            $decoded = json_decode($schemaRaw, true);
            if (is_array($decoded)) {
                $schema = $decoded;
            }
        }

        return [
            'id' => (int)$row['ID'],
            'formId' => (int)$row['FORM_ID'],
            'type' => (string)$row['VERSION_TYPE'],
            'name' => (string)$row['NAME'],
            'code' => (string)$row['CODE'],
            'status' => (string)$row['STATUS'],
            'schema' => $schema,
            'createdBy' => (int)$row['CREATED_BY'],
            'createdAt' => (string)$row['CREATED_AT'],
            'versionNumber' => 0,
        ];
    }

    /**
     * Возвращает имя ORM-таблицы.
     *
     * @return string
     */
    private function getTableName(): string
    {
        return TableNames::version();
    }
}
