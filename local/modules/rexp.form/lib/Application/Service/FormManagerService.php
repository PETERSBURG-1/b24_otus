<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Application\DTO\SaveFormCommand;

/**
 * Прикладной сервис управления формами.
 */
final class FormManagerService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormReadService $formReadService
     * @param FormPublishService $formPublishService
     */
    public function __construct(
        private readonly FormReadService $formReadService,
        private readonly FormPublishService $formPublishService,
    ) {
    }

    /**
     * Возвращает набор доступных разрешений текущего пользователя.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->formReadService->getPermissions();
    }

    /**
     * Возвращает список записей.
     *
     * @param bool $withArchived
     * @param int $userId
     *
     * @return array
     */
    public function list(bool $withArchived = false, int $userId = 0): array
    {
        return $this->formReadService->list($withArchived, $userId);
    }

    /**
     * Возвращает данные по переданным параметрам.
     *
     * @param int $id
     * @param int $userId
     *
     * @return ?array
     */
    public function get(int $id, int $userId = 0): ?array
    {
        return $this->formReadService->get($id, $userId);
    }

    /**
     * Сохраняет данные и возвращает результат операции.
     *
     * @param SaveFormCommand $command
     *
     * @return array
     */
    public function save(SaveFormCommand $command): array
    {
        return $this->formPublishService->save($command);
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
        $this->formPublishService->archive($id, $userId);
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
        $this->formPublishService->restore($id, $userId);
    }

    /**
     * Создаёт копию формы.
     *
     * @param int $id
     * @param int $userId
     *
     * @return ?array
     */
    public function duplicate(int $id, int $userId): ?array
    {
        return $this->formPublishService->duplicate($id, $userId);
    }

    /**
     * Удаляет данные.
     *
     * @param int $id
     * @param int $userId
     *
     * @return void
     */
    public function delete(int $id, int $userId): void
    {
        $this->formPublishService->delete($id, $userId);
    }
}

