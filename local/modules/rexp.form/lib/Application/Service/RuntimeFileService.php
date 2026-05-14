<?php

namespace Rexp\Form\Application\Service;

use RuntimeException;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис обработки файлов публичной формы.
 */
final class RuntimeFileService
{
    private const SAVE_DIR = 'rexp/form';

    /**
     * Объединяет значения файловых полей.
     *
     * @param array $schema
     * @param array $values
     * @param array $uploadedFiles
     * @param array $existingFilesState
     *
     * @return array
     */
    public function mergeFileValues(array $schema, array $values, array $uploadedFiles = [], array $existingFilesState = []): array
    {
        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (!is_array($field) || (string)($field['type'] ?? '') !== 'file') {
                continue;
            }

            $code = trim((string)($field['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $fileIds = [];
            $existing = $existingFilesState[$code] ?? $values[$code] ?? [];
            foreach ($this->normalizeExistingFileIds($existing) as $fileId) {
                $fileIds[] = $fileId;
            }

            foreach ($this->saveUploadedFiles($uploadedFiles[$code] ?? null) as $fileId) {
                $fileIds[] = $fileId;
            }

            $fileIds = array_values(array_unique(array_filter($fileIds)));
            $values[$code] = !empty($field['multiple']) ? $fileIds : ($fileIds[0] ?? null);
        }

        return $values;
    }

    /**
     * Нормализует existing файла ids.
     *
     * @param mixed $value
     *
     * @return array
     */
    private function normalizeExistingFileIds(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];
        $result = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['id'] ?? $item['fileId'] ?? $item['value'] ?? 0;
            }
            $id = (int)$item;
            if ($id > 0) {
                $result[] = $id;
            }
        }

        return $result;
    }

    /**
     * Сохраняет uploaded файлов.
     *
     * @param mixed $fileData
     *
     * @return array
     */
    private function saveUploadedFiles(mixed $fileData): array
    {
        if ($fileData === null || $fileData === []) {
            return [];
        }

        $files = $this->isSingleFile($fileData) ? [$fileData] : (is_array($fileData) ? $fileData : []);
        $result = [];

        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }

            $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFILESERVICE_001') . (string)($file['name'] ?? '') . '».');
            }

            $fileId = (int)\CFile::SaveFile($file, self::SAVE_DIR);
            if ($fileId <= 0) {
                throw new RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_RUNTIMEFILESERVICE_002') . (string)($file['name'] ?? '') . '».');
            }

            $result[] = $fileId;
        }

        return $result;
    }

    /**
     * Проверяет признак single файла.
     *
     * @param mixed $fileData
     *
     * @return bool
     */
    private function isSingleFile(mixed $fileData): bool
    {
        return is_array($fileData) && array_key_exists('tmp_name', $fileData);
    }
}
