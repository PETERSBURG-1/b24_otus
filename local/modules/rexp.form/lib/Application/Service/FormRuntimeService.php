<?php

namespace Rexp\Form\Application\Service;

use Rexp\Form\Infrastructure\Persistence\Orm\FormTable;
use RuntimeException;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис получения публичной формы и обработки отправки.
 */
class FormRuntimeService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param RuntimePublicFormBuilderService $publicFormBuilder
     * @param RuntimeSubmissionService $submissionService
     * @param RuntimeBizprocService $bizprocService
     * @param RuntimeFieldValidationService $validationService
     * @param RuntimeFileService $fileService
     * @param FormPermissionService $permissionService
     */
    public function __construct(
        private readonly RuntimePublicFormBuilderService $publicFormBuilder,
        private readonly RuntimeSubmissionService $submissionService,
        private readonly RuntimeBizprocService $bizprocService,
        private readonly RuntimeFieldValidationService $validationService,
        private readonly RuntimeFileService $fileService,
        private readonly FormPermissionService $permissionService,
    ) {
    }

    /**
     * Возвращает public формы по кода.
     *
     * @param string $code
     * @param int $entryId
     * @param string $mode
     *
     * @return array
     */
    public function getPublicFormByCode(string $code, int $entryId = 0, string $mode = 'create'): array
    {
        $this->permissionService->assertCanUsePublicForms();

        $code = trim($code);
        if ($code === '') {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRUNTIMESERVICE_001'));
        }

        $row = FormTable::getList([
            'select' => ['ID', 'CODE', 'NAME', 'ACTIVE', 'SCHEMA'],
            'filter' => [
                '=CODE' => $code,
                '=ACTIVE' => 'Y',
                '=ARCHIVED' => 'N',
            ],
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRUNTIMESERVICE_002'));
        }

        return $this->publicFormBuilder->build($row, $entryId, $mode);
    }

    /**
     * Обрабатывает отправку публичной формы по символьному коду.
     *
     * @param string $code
     * @param array $values
     * @param array $uploadedFiles
     * @param int $entryId
     * @param string $mode
     * @param array $existingFilesState
     *
     * @return array
     */
    public function submitByCode(
        string $code,
        array $values,
        array $uploadedFiles = [],
        int $entryId = 0,
        string $mode = 'create',
        array $existingFilesState = []
    ): array {
        $form = $this->getPublicFormByCode($code, $entryId, $mode);
        $schema = is_array($form['schema'] ?? null) ? $form['schema'] : [];
        $row = FormTable::getById((int)($form['id'] ?? 0))->fetch();
        if (!$row) {
            throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRUNTIMESERVICE_003'));
        }

        $runtimeMode = 'create';
        $valuesWithFiles = $this->fileService->mergeFileValues($schema, $values, $uploadedFiles, $existingFilesState);
        $submittedValues = $this->validationService->filterSubmittedValues($schema, $valuesWithFiles);
        $normalizedValues = $this->validationService->validateAndNormalize($schema, $valuesWithFiles);

        $submissionContext = [
            'uploadedFileFields' => array_values(array_intersect(array_keys($uploadedFiles), array_keys($submittedValues))),
            'submittedFieldCodes' => array_keys($submittedValues),
            'normalizedFieldCodes' => array_keys($normalizedValues),
            'ignoredFieldCodes' => array_values(array_diff(array_keys($valuesWithFiles), array_keys($submittedValues))),
        ];
        $submissionId = $this->submissionService->start(
            $row,
            $runtimeMode,
            0,
            $submittedValues,
            $submissionContext,
            $normalizedValues
        );

        try {
            $settings = is_array($schema['settings'] ?? null) ? $schema['settings'] : [];
            $successText = trim((string)($settings['successText'] ?? '')) ?: \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRUNTIMESERVICE_004');

            $result = [
                'entryId' => 0,
                'message' => $successText,
                'mode' => $runtimeMode,
                'submissionId' => $submissionId,
            ];

            $this->submissionService->markSuccess($submissionId, $normalizedValues, $result);
            $this->finalizeBizproc($submissionId, $normalizedValues, $result);

            return $result;
        } catch (\Throwable $exception) {
            $this->submissionService->markError($submissionId, $normalizedValues, $submissionContext, [$exception->getMessage()]);
            throw $exception;
        }
    }

    /**
     * Завершает обработку бизнес-процессов после отправки формы.
     *
     * @param int $submissionId
     * @param array $values
     * @param array $result
     *
     * @return void
     */
    private function finalizeBizproc(int $submissionId, array $values, array &$result): void
    {
        if ($submissionId <= 0) {
            return;
        }

        $bpErrors = $this->bizprocService->autoStartCreate($submissionId);
        if ($bpErrors === []) {
            return;
        }

        $result['bizprocErrors'] = $bpErrors;
        $errors = array_map(static function ($error): string {
            if (is_array($error)) {
                return (string)($error['message'] ?? $error['code'] ?? \Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMRUNTIMESERVICE_005'));
            }

            return (string)$error;
        }, $bpErrors);

        $this->submissionService->markPartialSuccess($submissionId, $values, $result, $errors);
    }
}
