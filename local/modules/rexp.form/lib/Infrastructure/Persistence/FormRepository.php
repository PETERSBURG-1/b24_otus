<?php

namespace Rexp\Form\Infrastructure\Persistence;

use Bitrix\Main\Type\DateTime;
use Rexp\Form\Application\DTO\SaveFormCommand;
use Rexp\Form\Application\Factory\FormFactory;
use Rexp\Form\Domain\Entity\Form;
use Rexp\Form\Domain\ValueObject\FormCode;
use Rexp\Form\Infrastructure\Persistence\Orm\FormDraftTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormSubmissionTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormTable;
use Rexp\Form\Infrastructure\Persistence\Orm\FormVersionTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Репозиторий форм.
 */
final class FormRepository
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormFactory $formFactory
     */
    public function __construct(private readonly FormFactory $formFactory)
    {
    }

    /** @return Form[] */
    /**
     * Возвращает список записей.
     *
     * @param bool $withArchived
     *
     * @return array
     */
    public function list(bool $withArchived = false): array
    {
        $params = ['order' => ['UPDATED_AT' => 'DESC', 'ID' => 'DESC']];
        if (!$withArchived) {
            $params['filter'] = ['=ARCHIVED' => 'N'];
        }

        $result = FormTable::getList($params);
        $items = [];
        while ($row = $result->fetch()) {
            $items[] = $this->formFactory->createFromRow($row);
        }

        return $items;
    }

    /**
     * Возвращает запись по идентификатору.
     *
     * @param int $id
     *
     * @return ?Form
     */
    public function getById(int $id): ?Form
    {
        if ($id <= 0) {
            return null;
        }

        $row = FormTable::getByPrimary($id)->fetch();
        return $row ? $this->formFactory->createFromRow($row) : null;
    }

    /**
     * Возвращает active по ID.
     *
     * @param int $id
     *
     * @return ?Form
     */
    public function getActiveById(int $id): ?Form
    {
        if ($id <= 0) {
            return null;
        }

        $row = FormTable::getList([
            'filter' => ['=ID' => $id, '=ACTIVE' => 'Y', '=ARCHIVED' => 'N'],
            'limit' => 1,
        ])->fetch();

        return $row ? $this->formFactory->createFromRow($row) : null;
    }

    /**
     * Возвращает по кода.
     *
     * @param string $code
     *
     * @return ?Form
     */
    public function getByCode(string $code): ?Form
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $row = FormTable::getList([
            'filter' => ['=CODE' => $code],
            'limit' => 1,
        ])->fetch();

        return $row ? $this->formFactory->createFromRow($row) : null;
    }

    /**
     * Возвращает active по кода.
     *
     * @param string $code
     *
     * @return ?Form
     */
    public function getActiveByCode(string $code): ?Form
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $row = FormTable::getList([
            'filter' => ['=CODE' => $code, '=ACTIVE' => 'Y', '=ARCHIVED' => 'N'],
            'limit' => 1,
        ])->fetch();

        return $row ? $this->formFactory->createFromRow($row) : null;
    }

    /**
     * Возвращает по кода except ID.
     *
     * @param string $code
     * @param int $excludeId
     *
     * @return ?Form
     */
    public function getByCodeExceptId(string $code, int $excludeId): ?Form
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $filter = ['=CODE' => $code];
        if ($excludeId > 0) {
            $filter['!=ID'] = $excludeId;
        }

        $row = FormTable::getList([
            'filter' => $filter,
            'limit' => 1,
        ])->fetch();

        return $row ? $this->formFactory->createFromRow($row) : null;
    }

    /**
     * Проверяет уникальность символьного кода формы.
     *
     * @param string $code
     * @param int $excludeId
     *
     * @return void
     */
    private function assertCodeIsUnique(string $code, int $excludeId = 0): void
    {
        if ($this->getByCodeExceptId($code, $excludeId)) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_INFRASTRUCTURE_PERSISTENCE_FORMREPOSITORY_001'));
        }
    }

    /**
     * Сохраняет данные и возвращает результат операции.
     *
     * @param SaveFormCommand $command
     *
     * @return Form
     */
    public function save(SaveFormCommand $command): Form
    {
        $formCode = new FormCode($command->code);
        $this->assertCodeIsUnique($formCode->getValue(), $command->id);
        $schema = json_encode($command->schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        $data = [
            'NAME' => $command->name,
            'CODE' => $formCode->getValue(),
            'ACTIVE' => $command->active ? 'Y' : 'N',
            'ARCHIVED' => $command->archived ? 'Y' : 'N',
            'SCHEMA' => $schema,
            'UPDATED_BY' => $command->userId,
            'UPDATED_AT' => new DateTime(),
        ];

        if ($command->id > 0) {
            $result = FormTable::update($command->id, $data);
            if (!$result->isSuccess()) {
                throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
            }

            return $this->getById($command->id);
        }

        $data['CREATED_BY'] = $command->userId;
        $data['CREATED_AT'] = new DateTime();
        $result = FormTable::add($data);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->getById((int)$result->getId());
    }

    /**
     * Генерирует уникальный символьный код формы.
     *
     * @param string $baseCode
     * @param string $suffix
     *
     * @return string
     */
    public function generateUniqueCode(string $baseCode, string $suffix = '_copy'): string
    {
        $baseCode = trim($baseCode);
        if ($baseCode === '') {
            $baseCode = 'form';
        }

        $base = $baseCode . $suffix;
        $candidate = $base;
        $index = 1;
        while ($this->getByCode($candidate)) {
            $index++;
            $candidate = $base . '_' . $index;
        }

        return $candidate;
    }

    /**
     * Перемещает форму в архив.
     *
     * @param int $id
     * @param int $userId
     *
     * @return void
     */
    public function archive(int $id, int $userId): void
    {
        if ($id <= 0) {
            return;
        }

        $result = FormTable::update($id, [
            'ARCHIVED' => 'Y',
            'UPDATED_BY' => $userId,
            'UPDATED_AT' => new DateTime(),
        ]);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }

    /**
     * Восстанавливает форму из архива или версии.
     *
     * @param int $id
     * @param int $userId
     *
     * @return void
     */
    public function restore(int $id, int $userId): void
    {
        if ($id <= 0) {
            return;
        }

        $result = FormTable::update($id, [
            'ARCHIVED' => 'N',
            'UPDATED_BY' => $userId,
            'UPDATED_AT' => new DateTime(),
        ]);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }

    /**
     * Создаёт копию формы.
     *
     * @param int $id
     * @param int $userId
     *
     * @return ?Form
     */
    public function duplicate(int $id, int $userId): ?Form
    {
        $form = $this->getById($id);
        if (!$form) {
            return null;
        }

        return $this->save(new SaveFormCommand(
            0,
            $form->getName() . \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_INFRASTRUCTURE_PERSISTENCE_FORMREPOSITORY_002'),
            $this->generateUniqueCode($form->getCode(), '_copy'),
            false,
            false,
            $form->getSchema(),
            $userId,
            true,
        ));
    }

    /**
     * Удаляет данные.
     *
     * @param int $id
     *
     * @return void
     */
    public function delete(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $this->deleteByFormId(FormDraftTable::class, $id);
        $this->deleteByFormId(FormVersionTable::class, $id);
        $this->deleteByFormId(FormSubmissionTable::class, $id);

        $result = FormTable::delete($id);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }

    /**
     * Удаляет связанные записи формы.
     *
     * @param string $tableClass
     * @param int $formId
     *
     * @return void
     */
    private function deleteByFormId(string $tableClass, int $formId): void
    {
        $rows = $tableClass::getList([
            'select' => ['ID'],
            'filter' => ['=FORM_ID' => $formId],
        ]);

        while ($row = $rows->fetch()) {
            $tableClass::delete((int)$row['ID']);
        }
    }
}
