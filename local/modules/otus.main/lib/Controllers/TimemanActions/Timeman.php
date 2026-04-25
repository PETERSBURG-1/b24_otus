<?php

namespace Otus\Main\Controllers\TimemanActions;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Контроллер для запуска или возобновления рабочего дня.
 */
class Timeman extends Controller
{
    /**
     * Возвращает настройки действий контроллера.
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'startDay' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
        ];
    }

    /**
     * Запускает или возобновляет рабочий день текущего пользователя.
     *
     * @param string $mode Режим запуска: open или reopen.
     *
     * @return array|null
     */
    public function startDayAction(string $mode = 'open'): ?array
    {
        global $APPLICATION;
        global $USER;

        if (!Loader::includeModule('timeman'))
        {
            $this->addError(new Error(Loc::getMessage('OTUS_MAIN_TIMEMAN_CONTROLLER_MODULE_ERROR')));
            return null;
        }

        if (!is_object($USER) || (int)$USER->GetID() <= 0)
        {
            $this->addError(new Error(Loc::getMessage('OTUS_MAIN_TIMEMAN_CONTROLLER_AUTH_ERROR')));
            return null;
        }

        $timeManUser = new \CTimeManUser((int)$USER->GetID());
        $userSettings = $timeManUser->GetSettings();

        if (empty($userSettings['UF_TIMEMAN']))
        {
            $this->addError(new Error(Loc::getMessage('OTUS_MAIN_TIMEMAN_CONTROLLER_DISABLED_ERROR')));
            return null;
        }

        $mode = mb_strtolower(trim($mode));
        $device = \Bitrix\Timeman\Model\Schedule\ScheduleTable::ALLOWED_DEVICES_BROWSER;

        if ($mode === 'reopen')
        {
            $result = $timeManUser->reopenDay(
                true,
                SITE_ID,
                [
                    'action' => 'reopen',
                    'DEVICE' => $device,
                ]
            );
        }
        else
        {
            $result = $timeManUser->openDay(
                false,
                '',
                [
                    'IP_OPEN' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'DEVICE' => $device,
                ]
            );
        }

        if ($result === false)
        {
            $exception = $APPLICATION->GetException();
            $message = $exception ? $exception->GetString() : Loc::getMessage('OTUS_MAIN_TIMEMAN_CONTROLLER_START_ERROR');

            $this->addError(new Error($message));
            return null;
        }

        return [
            'status' => 'success',
            'mode' => $mode,
        ];
    }
}
