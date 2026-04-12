<?php

namespace Otus\Main\Crm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Обработчик вкладок детальной карточки CRM.
 */
class EntityDetailsTabs
{
    /**
     * Идентификатор модуля.
     */
    protected const MODULE_ID = 'otus.main';

    /**
     * Добавляет кастомную вкладку в карточку CRM.
     *
     * @param Event $event Событие инициализации вкладок.
     *
     * @return EventResult
     */
    public static function onEntityDetailsTabsInitialized(Event $event)
    {
        $tabs = (array)$event->getParameter('tabs');
        $entityId = (int)$event->getParameter('entityID');
        $entityTypeId = (int)$event->getParameter('entityTypeID');

        if ($entityId <= 0 || $entityTypeId <= 0) {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        if (!Loader::includeModule(self::MODULE_ID)) {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        $tabs[] = [
            'id' => 'otus_crm_external_data',
            'name' => Loc::getMessage('OTUS_MAIN_TAB_NAME'),
            'loader' => [
                'serviceUrl' => sprintf(
                    '/local/components/otus/crm.entity.data.grid/lazyload.ajax.php?site=%s&%s',
                    SITE_ID,
                    bitrix_sessid_get()
                ),
                'componentData' => [
                    'params' => [
                        'ENTITY_TYPE_ID' => $entityTypeId,
                        'ENTITY_ID' => $entityId,
                        'CACHE_TYPE' => 'N',
                        'CACHE_TIME' => 0,
                    ],
                    'template' => '.default',
                ],
            ],
        ];

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }
}
