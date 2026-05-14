<?php

namespace Rexp\Form\Application\UseCase\Designer;

use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Application\Service\MockSubmitDiagnosticsService;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Use case диагностической отправки формы из редактора.
 */
final class DesignerSubmitUseCase
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormReadService $readService
     * @param MockSubmitDiagnosticsService $mockDiagnosticsService
     */
    public function __construct(
        private readonly FormReadService $readService,
        private readonly MockSubmitDiagnosticsService $mockDiagnosticsService,
    ) {
    }

    /**
     * Выполняет пробную отправку формы.
     *
     * @param int $id
     * @param array $values
     * @param int $userId
     *
     * @return array
     */
    public function probeSubmit(int $id, array $values, int $userId): array
    {
        $item = $this->readService->get($id, $userId);
        if (!$item) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_USECASE_DESIGNER_DESIGNERSUBMITUSECASE_001'));
        }

        return [
            'item' => $this->mockDiagnosticsService->probe($item, $values),
            'permissions' => $this->readService->getPermissions(),
        ];
    }

    /**
     * Запускает диагностику тестовой отправки формы.
     *
     * @param int $id
     * @param int $userId
     *
     * @return array
     */
    public function runMockSubmitDiagnostics(int $id, int $userId): array
    {
        $item = $this->readService->get($id, $userId);
        if (!$item) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_USECASE_DESIGNER_DESIGNERSUBMITUSECASE_002'));
        }

        return [
            'item' => $this->mockDiagnosticsService->run(is_array($item['schema'] ?? null) ? $item['schema'] : []),
            'permissions' => $this->readService->getPermissions(),
        ];
    }
}
