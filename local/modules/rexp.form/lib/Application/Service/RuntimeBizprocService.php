<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Loader;
use Rexp\Form\Bizproc\FormSubmissionDocument;
use Rexp\Form\Infrastructure\Persistence\Orm\FormSubmissionTable;

/**
 * Сервис запуска бизнес-процессов по отправке формы.
 */
final class RuntimeBizprocService
{
    /**
     * Автоматически запускает бизнес-процесс создания документа.
     *
     * @param int $submissionId
     *
     * @return array
     */
    public function autoStartCreate(int $submissionId): array
    {
        if ($submissionId <= 0 || !Loader::includeModule('bizproc')) {
            return [];
        }

        Loader::includeModule('crm');

        if ($submissionId <= 0) {
            return [];
        }

        $submission = FormSubmissionTable::getById($submissionId)->fetch();
        if (!$submission) {
            return [];
        }

        $formId = (int)($submission['FORM_ID'] ?? 0);
        if ($formId <= 0) {
            return [];
        }

        $errors = [];
        \CBPDocument::AutoStartWorkflows(
            FormSubmissionDocument::getComplexDocumentType($formId),
            \CBPDocumentEventType::Create,
            FormSubmissionDocument::getComplexDocumentId($submissionId),
            [],
            $errors
        );

        return is_array($errors) ? array_values($errors) : [];
    }
}
