<?php

namespace Rexp\Form\Application\Service;

use Bitrix\Main\Type\DateTime;
use Rexp\Form\Infrastructure\Event\FormEventDispatcher;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Сервис подготовки формы к экспорту.
 */
final class FormExportService
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param FormEventDispatcher $eventDispatcher
     */
    public function __construct(private readonly FormEventDispatcher $eventDispatcher)
    {
    }

    /**
     * Подготавливает данные формы к экспорту.
     *
     * @param array $item
     *
     * @return array
     */
    public function export(array $item): array
    {
        if (empty($item['id'])) {
            throw new \RuntimeException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_APPLICATION_SERVICE_FORMEXPORTSERVICE_001'));
        }

        $payload = [
            'name' => (string)$item['name'],
            'code' => (string)$item['code'],
            'exportedAt' => (new DateTime())->toString(),
            'schemaVersion' => 1,
            'module' => 'rexp.form',
            'form' => [
                'name' => (string)$item['name'],
                'code' => (string)$item['code'],
                'status' => !empty($item['active']) ? 'published' : 'draft',
                'schema' => is_array($item['schema'] ?? null) ? $item['schema'] : [],
            ],
        ];

        $this->eventDispatcher->dispatch('onFormExport', [
            'formId' => (int)$item['id'],
            'payload' => $payload,
        ]);

        return $payload;
    }
}
