<?php

namespace Rexp\Form\Infrastructure\Persistence;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormDraftTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Репозиторий черновиков форм.
 */
final class FormDraftRepository
{
    /**
     * Сохраняет данные и возвращает результат операции.
     *
     * @param int $formId
     * @param int $userId
     * @param array $form
     *
     * @return array
     */
    public function save(int $formId, int $userId, array $form): array
    {
        if ($formId <= 0 || $userId <= 0) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_INFRASTRUCTURE_PERSISTENCE_FORMDRAFTREPOSITORY_001'));
        }

        if (!Application::getConnection()->isTableExists(TableNames::draft())) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_INFRASTRUCTURE_PERSISTENCE_FORMDRAFTREPOSITORY_002'));
        }

        $payload = [
            'formId' => $formId,
            'authorId' => $userId,
            'updatedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'form' => $form,
        ];

        $formJson = json_encode($form, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $existing = FormDraftTable::getList([
            'filter' => ['=FORM_ID' => $formId, '=USER_ID' => $userId],
            'limit' => 1,
            'select' => ['ID'],
        ])->fetch();

        if ($existing) {
            $result = FormDraftTable::update((int)$existing['ID'], [
                'FORM_JSON' => $formJson,
                'UPDATED_AT' => new DateTime(),
            ]);
        } else {
            $result = FormDraftTable::add([
                'FORM_ID' => $formId,
                'USER_ID' => $userId,
                'FORM_JSON' => $formJson,
                'UPDATED_AT' => new DateTime(),
            ]);
        }

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->get($formId, $userId) ?: $this->decorate($payload);
    }

    /**
     * Возвращает данные по переданным параметрам.
     *
     * @param int $formId
     * @param int $userId
     *
     * @return ?array
     */
    public function get(int $formId, int $userId): ?array
    {
        if (!Application::getConnection()->isTableExists(TableNames::draft())) {
            return null;
        }

        $row = FormDraftTable::getList([
            'filter' => ['=FORM_ID' => $formId, '=USER_ID' => $userId],
            'order' => ['ID' => 'DESC'],
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            return null;
        }

        $form = [];
        $formJson = (string)($row['FORM_JSON'] ?? '');
        if ($formJson !== '') {
            $decoded = json_decode($formJson, true);
            if (is_array($decoded)) {
                $form = $decoded;
            }
        }

        if ($form === []) {
            return null;
        }

        return $this->decorate([
            'formId' => (int)$row['FORM_ID'],
            'authorId' => (int)$row['USER_ID'],
            'updatedAt' => (string)($row['UPDATED_AT'] ?? ''),
            'form' => $form,
        ]);
    }


    /**
     * Возвращает запись по идентификатору.
     *
     * @param int $draftId
     *
     * @return ?array
     */
    public function getById(int $draftId): ?array
    {
        if ($draftId <= 0 || !Application::getConnection()->isTableExists(TableNames::draft())) {
            return null;
        }

        $row = FormDraftTable::getByPrimary($draftId)->fetch();
        if (!$row) {
            return null;
        }

        $form = [];
        $formJson = (string)($row['FORM_JSON'] ?? '');
        if ($formJson !== '') {
            $decoded = json_decode($formJson, true);
            if (is_array($decoded)) {
                $form = $decoded;
            }
        }

        return $this->decorate([
            'id' => (int)$row['ID'],
            'formId' => (int)$row['FORM_ID'],
            'authorId' => (int)$row['USER_ID'],
            'updatedAt' => (string)($row['UPDATED_AT'] ?? ''),
            'form' => $form,
        ]);
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
        if ($formId <= 0 || !Application::getConnection()->isTableExists(TableNames::draft())) {
            return [];
        }

        $items = [];
        $rows = FormDraftTable::getList([
            'filter' => ['=FORM_ID' => $formId],
            'order' => ['UPDATED_AT' => 'DESC', 'ID' => 'DESC'],
            'select' => ['ID', 'FORM_ID', 'USER_ID', 'FORM_JSON', 'UPDATED_AT'],
        ]);

        while ($row = $rows->fetch()) {
            $form = [];
            $formJson = (string)($row['FORM_JSON'] ?? '');
            if ($formJson !== '') {
                $decoded = json_decode($formJson, true);
                if (is_array($decoded)) {
                    $form = $decoded;
                }
            }

            $items[] = $this->decorate([
                'id' => (int)$row['ID'],
                'formId' => (int)$row['FORM_ID'],
                'authorId' => (int)$row['USER_ID'],
                'updatedAt' => (string)($row['UPDATED_AT'] ?? ''),
                'form' => $form,
                'fieldsCount' => count(is_array($form['fields'] ?? null) ? $form['fields'] : []),
                'sectionsCount' => count(is_array($form['sections'] ?? null) ? $form['sections'] : []),
            ]);
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
        if (!Application::getConnection()->isTableExists(TableNames::draft())) {
            return [];
        }

        $items = [];
        $rows = FormDraftTable::getList([
            'order' => ['UPDATED_AT' => 'DESC', 'ID' => 'DESC'],
            'select' => ['ID', 'FORM_ID', 'USER_ID', 'FORM_JSON', 'UPDATED_AT'],
        ]);

        while ($row = $rows->fetch()) {
            $form = [];
            $formJson = (string)($row['FORM_JSON'] ?? '');
            if ($formJson !== '') {
                $decoded = json_decode($formJson, true);
                if (is_array($decoded)) {
                    $form = $decoded;
                }
            }

            $items[] = $this->decorate([
                'id' => (int)$row['ID'],
                'formId' => (int)$row['FORM_ID'],
                'authorId' => (int)$row['USER_ID'],
                'updatedAt' => (string)($row['UPDATED_AT'] ?? ''),
                'form' => $form,
                'fieldsCount' => count(is_array($form['fields'] ?? null) ? $form['fields'] : []),
                'sectionsCount' => count(is_array($form['sections'] ?? null) ? $form['sections'] : []),
            ]);
        }

        return $items;
    }

    /**
     * Удаляет запись по идентификатору.
     *
     * @param int $draftId
     *
     * @return void
     */
    public function deleteById(int $draftId): void
    {
        if ($draftId <= 0 || !Application::getConnection()->isTableExists(TableNames::draft())) {
            return;
        }

        FormDraftTable::delete($draftId);
    }

    /**
     * Очищает данные по переданным параметрам.
     *
     * @param int $formId
     * @param int $userId
     *
     * @return void
     */
    public function clear(int $formId, int $userId): void
    {
        if (!Application::getConnection()->isTableExists(TableNames::draft())) {
            return;
        }

        $rows = FormDraftTable::getList([
            'filter' => ['=FORM_ID' => $formId, '=USER_ID' => $userId],
            'select' => ['ID'],
        ]);

        while ($row = $rows->fetch()) {
            FormDraftTable::delete((int)$row['ID']);
        }
    }

    /**
     * Возвращает карту метаданных по идентификаторам форм.
     *
     * @param array $formIds
     * @param int $userId
     *
     * @return array
     */
    public function getMetaMap(array $formIds, int $userId): array
    {
        $formIds = array_values(array_filter(array_map('intval', $formIds), static fn(int $id): bool => $id > 0));
        if ($formIds === []) {
            return [];
        }

        if (!Application::getConnection()->isTableExists(TableNames::draft())) {
            return [];
        }

        $result = [];
        $rows = FormDraftTable::getList([
            'filter' => ['=USER_ID' => $userId, '@FORM_ID' => $formIds],
            'select' => ['FORM_ID', 'USER_ID', 'UPDATED_AT'],
        ]);

        while ($row = $rows->fetch()) {
            $formId = (int)($row['FORM_ID'] ?? 0);
            if ($formId <= 0) {
                continue;
            }

            $result[$formId] = [
                'hasDraft' => true,
                'updatedAt' => (string)($row['UPDATED_AT'] ?? ''),
                'authorId' => (int)($row['USER_ID'] ?? 0),
                'authorName' => $this->resolveUserName((int)($row['USER_ID'] ?? 0)),
            ];
        }

        return $result;
    }

    /**
     * Дополняет запись производными данными для вывода.
     *
     * @param array $payload
     *
     * @return array
     */
    private function decorate(array $payload): array
    {
        $payload['authorId'] = (int)($payload['authorId'] ?? 0);
        $payload['updatedAt'] = (string)($payload['updatedAt'] ?? '');
        $payload['authorName'] = $this->resolveUserName($payload['authorId']);

        return $payload;
    }

    /**
     * Определяет пользователя name.
     *
     * @param int $userId
     *
     * @return string
     */
    private function resolveUserName(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        $row = UserTable::getById($userId)->fetch();
        if (!$row) {
            return '';
        }

        $name = trim((string)($row['NAME'] ?? '') . ' ' . (string)($row['LAST_NAME'] ?? ''));
        return $name !== '' ? $name : (string)($row['LOGIN'] ?? ('User #' . $userId));
    }
}
