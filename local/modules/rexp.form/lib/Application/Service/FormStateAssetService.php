<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\IO\InvalidPathException;
use CFile;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис загрузки и хранения изображений состояния формы.
 */
final class FormStateAssetService
{
    private const MAX_IMAGE_SIZE = 5_242_880; // 5 MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /** @param array<string,mixed> $file */
    /**
     * Загружает изображение состояния формы.
     *
     * @param array $file
     *
     * @return array
     */
    public function uploadImage(array $file): array
    {
        $originalName = trim((string)($file['name'] ?? ''));
        if ($originalName === '') {
            throw new ArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_001'));
        }

        $tmpName = trim((string)($file['tmp_name'] ?? ''));
        if ($tmpName === '' || !is_file($tmpName)) {
            throw new ArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_002'));
        }

        $errorCode = (int)($file['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new ArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_003') . $errorCode);
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            throw new ArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_004'));
        }

        if ($size > self::MAX_IMAGE_SIZE) {
            throw new ArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_005'));
        }

        $extension = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new ArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_006'));
        }

        if (!$this->isImageFile($tmpName, $extension)) {
            throw new ArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_007'));
        }

        $preparedFile = $file;
        $preparedFile['MODULE_ID'] = 'rexp.form';
        $preparedFile['del'] = 'N';

        $fileId = (int)CFile::SaveFile($preparedFile, 'rexp.form/state');
        if ($fileId <= 0) {
            throw new InvalidPathException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_008'));
        }

        $url = (string)CFile::GetPath($fileId);
        if ($url === '') {
            throw new InvalidPathException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMSTATEASSETSERVICE_009'));
        }

        return [
            'fileId' => $fileId,
            'url' => $url,
            'name' => $originalName,
            'size' => $size,
            'extension' => $extension,
        ];
    }

    /**
     * Проверяет признак image файла.
     *
     * @param string $tmpName
     * @param string $extension
     *
     * @return bool
     */
    private function isImageFile(string $tmpName, string $extension): bool
    {
        if ($extension === 'svg') {
            return true;
        }

        $imageInfo = @getimagesize($tmpName);
        return is_array($imageInfo) && !empty($imageInfo[0]) && !empty($imageInfo[1]);
    }
}
