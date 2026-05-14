<?php

namespace Rexp\Form\Controller;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use RuntimeException;
use Throwable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Контроллер публичной формы.
 */
class Runtime extends Controller
{
    /**
     * Описывает AJAX-действия контроллера.
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'getForm' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'submit' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'getDynamicItems' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
        ];
    }

    /**
     * Возвращает публичную форму по символьному коду.
     *
     * @param string $code
     * @param int $entryId
     * @param string $mode
     *
     * @return ?array
     */
    public function getFormAction(string $code, int $entryId = 0, string $mode = 'create'): ?array
    {
        try {
            return [
                'item' => $this->runtime()->getPublicFormByCode($code, $entryId, $mode),
            ];
        } catch (Throwable $exception) {
            $this->addError(new Error($exception->getMessage()));
            return null;
        }
    }


    /**
     * Возвращает динамические элементы для поля формы.
     *
     * @param string $provider
     * @param mixed $sourceValue
     * @param array $config
     *
     * @return ?array
     */
    public function getDynamicItemsAction(string $provider, mixed $sourceValue = null, array $config = []): ?array
    {
        try {
            return [
                'items' => $this->dynamicOptions()->getItems($provider, $sourceValue, $config),
            ];
        } catch (Throwable $exception) {
            $this->addError(new Error($exception->getMessage()));
            return null;
        }
    }

    /**
     * Обрабатывает публичную отправку формы.
     *
     * @return ?array
     */
    public function submitAction(): ?array
    {
        try {
            $request = $this->getRequest();
            $code = trim((string)$request->getPost('code'));
            $entryId = (int)$request->getPost('entry_id');
            $mode = trim((string)$request->getPost('mode')) ?: 'create';

            $valuesJson = (string)$request->getPost('values_json');
            $values = [];
            if ($valuesJson !== '') {
                $decoded = json_decode($valuesJson, true);
                if (is_array($decoded)) {
                    $values = $decoded;
                }
            }

            $existingFilesStateJson = (string)$request->getPost('existing_files_json');
            $existingFilesState = [];
            if ($existingFilesStateJson !== '') {
                $decodedExistingFilesState = json_decode($existingFilesStateJson, true);
                if (is_array($decodedExistingFilesState)) {
                    $existingFilesState = $decodedExistingFilesState;
                }
            }

            $uploadedFiles = [];
            foreach ($_FILES as $key => $file) {
                if (!str_starts_with((string)$key, 'upload_')) {
                    continue;
                }

                $fieldCode = substr((string)$key, 7);
                if (!is_array($file)) {
                    continue;
                }

                if (is_array($file['name'] ?? null)) {
                    $uploadedFiles[$fieldCode] = $this->normalizeMultiFileArray($file);
                } else {
                    $uploadedFiles[$fieldCode] = $file;
                }
            }

            $beforeSubmit = $this->events()->dispatchCancelable('onRuntimeBeforeSubmit', [
                'code' => $code,
                'entryId' => $entryId,
                'mode' => $mode,
                'values' => $values,
                'uploadedFiles' => $uploadedFiles,
                'existingFilesState' => $existingFilesState,
            ]);

            if (!empty($beforeSubmit['errors'])) {
                foreach ($beforeSubmit['errors'] as $message) {
                    $this->addError(new Error((string)$message));
                }
            }

            if (!empty($beforeSubmit['canceled']) || !empty($beforeSubmit['errors'])) {
                throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_CONTROLLER_RUNTIME_001'));
            }

            $runtimeParameters = is_array($beforeSubmit['parameters'] ?? null) ? $beforeSubmit['parameters'] : [];
            $code = trim((string)($runtimeParameters['code'] ?? $code));
            $entryId = (int)($runtimeParameters['entryId'] ?? $entryId);
            $mode = trim((string)($runtimeParameters['mode'] ?? $mode)) ?: 'create';
            $values = is_array($runtimeParameters['values'] ?? null) ? $runtimeParameters['values'] : $values;
            $uploadedFiles = is_array($runtimeParameters['uploadedFiles'] ?? null) ? $runtimeParameters['uploadedFiles'] : $uploadedFiles;
            $existingFilesState = is_array($runtimeParameters['existingFilesState'] ?? null) ? $runtimeParameters['existingFilesState'] : $existingFilesState;

            $result = $this->runtime()->submitByCode($code, $values, $uploadedFiles, $entryId, $mode, $existingFilesState);

            $this->events()->dispatch('onRuntimeAfterSubmit', [
                'code' => $code,
                'entryId' => (int)($result['entryId'] ?? 0),
                'mode' => (string)($result['mode'] ?? $mode),
                'values' => $values,
                'result' => $result,
                'submissionId' => (int)($result['submissionId'] ?? 0),
            ]);

            return [
                'success' => true,
                'entryId' => $result['entryId'],
                'message' => $result['message'],
                'mode' => $result['mode'],
                'submissionId' => (int)($result['submissionId'] ?? 0),
            ];
        } catch (Throwable $exception) {
            $this->events()->dispatch('onRuntimeSubmitFailed', [
                'code' => $code ?? '',
                'entryId' => $entryId ?? 0,
                'mode' => $mode ?? 'create',
                'values' => $values ?? [],
                'message' => $exception->getMessage(),
            ]);
            if (!$this->getErrors()) {
                $this->addError(new Error($exception->getMessage()));
            }
            return null;
        }
    }

    /**
     * Нормализует структуру множественного файлового поля.
     *
     * @param array $file
     *
     * @return array
     */
    private function normalizeMultiFileArray(array $file): array
    {
        $result = [];
        $names = is_array($file['name'] ?? null) ? $file['name'] : [];

        foreach ($names as $index => $name) {
            $result[] = [
                'name' => $name,
                'type' => $file['type'][$index] ?? '',
                'tmp_name' => $file['tmp_name'][$index] ?? '',
                'error' => $file['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file['size'][$index] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Возвращает сервис публичной формы.
     *
     * @return mixed
     */
    private function runtime()
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.runtime');
    }

    /**
     * Возвращает сервис динамических вариантов полей.
     *
     * @return mixed
     */
    private function dynamicOptions()
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.runtime_dynamic_options');
    }

    /**
     * Возвращает диспетчер событий модуля.
     *
     * @return mixed
     */
    private function events()
    {
        return ServiceLocator::getInstance()->get('rexp.form.event.dispatcher');
    }
}
