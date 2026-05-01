<?php

namespace Otus\Main\Iblock;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Синхронизирует заявки инфоблока со сделками CRM.
 */
class RequestDealSync
{
    /**
     * Флаг защиты от зацикливания обработчиков.
     *
     * @var bool
     */
    private static bool $isSyncRunning = false;

    /**
     * Код инфоблока заявок, созданного вручную.
     *
     * @var string
     */
    private const IBLOCK_CODE = 'otus_requests';

    /**
     * Код свойства с привязкой к сделке.
     *
     * @var string
     */
    private const PROPERTY_DEAL = 'DEAL';

    /**
     * Код свойства суммы.
     *
     * @var string
     */
    private const PROPERTY_AMOUNT = 'AMOUNT';

    /**
     * Код свойства ответственного.
     *
     * @var string
     */
    private const PROPERTY_RESPONSIBLE = 'RESPONSIBLE';

    /**
     * Обрабатывает создание элемента инфоблока.
     *
     * @param array<string, mixed> $fields Поля созданного элемента.
     *
     * @return void
     */
    public static function onAfterIBlockElementAdd(array &$fields): void
    {
        if (!static::isSuccessfulElementEvent($fields)) {
            return;
        }

        static::syncDealFromRequest(static::getElementIdFromAfterEvent($fields), 'add');
    }

    /**
     * Обрабатывает изменение элемента инфоблока.
     *
     * @param array<string, mixed> $fields Поля измененного элемента.
     *
     * @return void
     */
    public static function onAfterIBlockElementUpdate(array &$fields): void
    {
        if (!static::isSuccessfulElementEvent($fields)) {
            return;
        }

        static::syncDealFromRequest(static::getElementIdFromAfterEvent($fields), 'update');
    }

    /**
     * Обрабатывает изменение свойств элемента инфоблока через SetPropertyValuesEx.
     *
     * @param int $elementId ID элемента.
     * @param int $iblockId ID инфоблока.
     * @param array<string, mixed> $propertyValues Новые значения свойств.
     * @param array<string, mixed> $flags Дополнительные флаги сохранения.
     *
     * @return void
     */
    public static function onAfterIBlockElementSetPropertyValuesEx(
        int $elementId,
        int $iblockId,
        array $propertyValues,
        array $flags = []
    ): void {
        if ($iblockId !== static::getRequestIblockId()) {
            return;
        }

        static::syncDealFromRequest($elementId, 'update');
    }

    /**
     * Обрабатывает удаление элемента инфоблока.
     *
     * @param int $elementId ID удаляемого элемента.
     *
     * @return void
     */
    public static function onBeforeIBlockElementDelete(int $elementId): void
    {
        if (static::$isSyncRunning || !static::includeRequiredModules()) {
            return;
        }

        $element = static::getRequestElement($elementId);
        if ($element === null) {
            return;
        }

        $properties = static::getRequestProperties($elementId, (int)$element['IBLOCK_ID']);
        $dealId = static::normalizeDealId($properties[static::PROPERTY_DEAL] ?? null);
        if ($dealId <= 0) {
            return;
        }

        static::addDealEvent(
            $dealId,
            Loc::getMessage('OTUS_MAIN_REQUEST_DEAL_EVENT_DELETE_NAME'),
            Loc::getMessage(
                'OTUS_MAIN_REQUEST_DEAL_EVENT_DELETE_TEXT',
                [
                    '#ELEMENT_ID#' => (string)$elementId,
                    '#ELEMENT_NAME#' => (string)($element['NAME'] ?? ''),
                ]
            )
        );
    }

    /**
     * Обрабатывает создание сделки CRM.
     *
     * @param array<string, mixed> $fields Поля созданной сделки.
     *
     * @return void
     */
    public static function onAfterCrmDealAdd(array &$fields): void
    {
        static::syncRequestsFromDeal((int)($fields['ID'] ?? 0));
    }

    /**
     * Обрабатывает изменение сделки CRM.
     *
     * @param array<string, mixed> $fields Поля измененной сделки.
     *
     * @return void
     */
    public static function onAfterCrmDealUpdate(array &$fields): void
    {
        static::syncRequestsFromDeal((int)($fields['ID'] ?? 0));
    }

    /**
     * Обрабатывает удаление сделки CRM.
     *
     * @param int $dealId ID удаляемой сделки.
     *
     * @return void
     */
    public static function onBeforeCrmDealDelete(int $dealId): void
    {
        if (static::$isSyncRunning || !static::includeRequiredModules()) {
            return;
        }

        $requestIds = static::getRequestIdsByDeal($dealId);
        if ($requestIds === []) {
            return;
        }

        static::$isSyncRunning = true;
        foreach ($requestIds as $requestId) {
            \CIBlockElement::SetPropertyValuesEx(
                $requestId,
                static::getRequestIblockId(),
                [
                    static::PROPERTY_DEAL => '',
                ]
            );
        }
        static::$isSyncRunning = false;
    }

    /**
     * Обновляет сделку данными из заявки.
     *
     * @param int $elementId ID элемента инфоблока.
     * @param string $eventType Тип события.
     *
     * @return void
     */
    private static function syncDealFromRequest(int $elementId, string $eventType): void
    {
        if (static::$isSyncRunning || $elementId <= 0 || !static::includeRequiredModules()) {
            return;
        }

        $element = static::getRequestElement($elementId);
        if ($element === null) {
            return;
        }

        $iblockId = (int)$element['IBLOCK_ID'];
        $properties = static::getRequestProperties($elementId, $iblockId);
        $dealId = static::normalizeDealId($properties[static::PROPERTY_DEAL] ?? null);
        if ($dealId <= 0) {
            return;
        }

        $deal = static::getDeal($dealId);
        if ($deal === null) {
            return;
        }

        $money = static::normalizeMoney($properties[static::PROPERTY_AMOUNT] ?? null);
        $responsibleId = static::normalizeUserId($properties[static::PROPERTY_RESPONSIBLE] ?? null);
        $dealFields = static::prepareDealFields($deal, $money, $responsibleId);
        if ($dealFields === []) {
            return;
        }

        static::$isSyncRunning = true;
        $dealObject = new \CCrmDeal(false);
        $isUpdated = $dealObject->Update($dealId, $dealFields, true, true, [
            'DISABLE_USER_FIELD_CHECK' => true,
            'REGISTER_SONET_EVENT' => true,
        ]);
        static::$isSyncRunning = false;

        if (!$isUpdated) {
            return;
        }

        static::addDealEvent(
            $dealId,
            static::getDealEventNameByType($eventType),
            Loc::getMessage(
                'OTUS_MAIN_REQUEST_DEAL_EVENT_SYNC_TEXT',
                [
                    '#ELEMENT_ID#' => (string)$elementId,
                    '#ELEMENT_NAME#' => (string)($element['NAME'] ?? ''),
                    '#FIELDS#' => static::getFieldNamesText($dealFields),
                ]
            )
        );
    }

    /**
     * Обновляет заявки данными из сделки.
     *
     * @param int $dealId ID сделки.
     *
     * @return void
     */
    private static function syncRequestsFromDeal(int $dealId): void
    {
        if (static::$isSyncRunning || $dealId <= 0 || !static::includeRequiredModules()) {
            return;
        }

        $deal = static::getDeal($dealId);
        if ($deal === null) {
            return;
        }

        $requestIds = static::getRequestIdsByDeal($dealId);
        if ($requestIds === []) {
            return;
        }

        $moneyValue = static::formatMoneyValue(
            static::normalizeAmount($deal['OPPORTUNITY'] ?? null),
            static::normalizeCurrencyId($deal['CURRENCY_ID'] ?? null)
        );
        $responsibleId = static::normalizeUserId($deal['ASSIGNED_BY_ID'] ?? null);

        static::$isSyncRunning = true;
        foreach ($requestIds as $requestId) {
            \CIBlockElement::SetPropertyValuesEx(
                $requestId,
                static::getRequestIblockId(),
                [
                    static::PROPERTY_AMOUNT => $moneyValue,
                    static::PROPERTY_RESPONSIBLE => $responsibleId,
                ]
            );
        }
        static::$isSyncRunning = false;
    }

    /**
     * Проверяет, что событие элемента инфоблока завершилось успешно.
     *
     * @param array<string, mixed> $fields Поля события.
     *
     * @return bool
     */
    private static function isSuccessfulElementEvent(array $fields): bool
    {
        if (array_key_exists('RESULT', $fields) && !$fields['RESULT']) {
            return false;
        }

        return empty($fields['RESULT_MESSAGE']);
    }

    /**
     * Возвращает ID элемента из массива события инфоблока.
     *
     * @param array<string, mixed> $fields Поля события.
     *
     * @return int
     */
    private static function getElementIdFromAfterEvent(array $fields): int
    {
        $elementId = (int)($fields['ID'] ?? 0);
        if ($elementId > 0) {
            return $elementId;
        }

        return (int)($fields['RESULT'] ?? 0);
    }

    /**
     * Подключает необходимые модули.
     *
     * @return bool
     */
    private static function includeRequiredModules(): bool
    {
        return Loader::includeModule('iblock') && Loader::includeModule('crm');
    }

    /**
     * Возвращает ID инфоблока заявок по его коду.
     *
     * @return int
     */
    private static function getRequestIblockId(): int
    {
        static $iblockId = null;

        if ($iblockId !== null) {
            return $iblockId;
        }

        if (!Loader::includeModule('iblock')) {
            $iblockId = 0;
            return $iblockId;
        }

        $iblock = \CIBlock::GetList(
            [],
            [
                '=CODE' => static::IBLOCK_CODE,
                'CHECK_PERMISSIONS' => 'N',
            ]
        )->Fetch();

        $iblockId = is_array($iblock) ? (int)$iblock['ID'] : 0;

        return $iblockId;
    }

    /**
     * Возвращает элемент инфоблока, если он относится к заявкам.
     *
     * @param int $elementId ID элемента.
     *
     * @return array<string, mixed>|null
     */
    private static function getRequestElement(int $elementId): ?array
    {
        $iblockId = static::getRequestIblockId();
        if ($iblockId <= 0) {
            return null;
        }

        $element = \CIBlockElement::GetList(
            [],
            [
                'ID' => $elementId,
                'IBLOCK_ID' => $iblockId,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
            ]
        )->Fetch();

        return is_array($element) ? $element : null;
    }

    /**
     * Возвращает значения свойств заявки.
     *
     * @param int $elementId ID элемента.
     * @param int $iblockId ID инфоблока.
     *
     * @return array<string, mixed>
     */
    private static function getRequestProperties(int $elementId, int $iblockId): array
    {
        $result = [];
        $properties = \CIBlockElement::GetProperty($iblockId, $elementId, [], []);
        while ($property = $properties->Fetch()) {
            $code = (string)($property['CODE'] ?? '');
            if ($code === '') {
                continue;
            }

            if (!array_key_exists($code, $result) || $result[$code] === null || $result[$code] === '') {
                $result[$code] = $property['VALUE'];
            }
        }

        return $result;
    }

    /**
     * Возвращает сделку CRM.
     *
     * @param int $dealId ID сделки.
     *
     * @return array<string, mixed>|null
     */
    private static function getDeal(int $dealId): ?array
    {
        $deal = \CCrmDeal::GetListEx(
            [],
            [
                'ID' => $dealId,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'OPPORTUNITY',
                'ASSIGNED_BY_ID',
                'CURRENCY_ID',
            ]
        )->Fetch();

        return is_array($deal) ? $deal : null;
    }

    /**
     * Возвращает список заявок, привязанных к сделке.
     *
     * @param int $dealId ID сделки.
     *
     * @return array<int, int>
     */
    private static function getRequestIdsByDeal(int $dealId): array
    {
        $iblockId = static::getRequestIblockId();
        if ($iblockId <= 0) {
            return [];
        }

        $values = [
            $dealId,
            (string)$dealId,
            'D_' . $dealId,
            'DEAL_' . $dealId,
        ];

        $requestIds = [];
        $elements = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'PROPERTY_' . static::PROPERTY_DEAL => $values,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            [
                'ID',
            ]
        );

        while ($element = $elements->Fetch()) {
            $requestIds[] = (int)$element['ID'];
        }

        return $requestIds;
    }

    /**
     * Подготавливает поля сделки для обновления.
     *
     * @param array<string, mixed> $deal Текущие поля сделки.
     * @param array{amount: float|null, currency: string|null} $money Сумма и валюта из заявки.
     * @param int $responsibleId Ответственный из заявки.
     *
     * @return array<string, mixed>
     */
    private static function prepareDealFields(array $deal, array $money, int $responsibleId): array
    {
        $fields = [];
        $amount = $money['amount'];
        $currencyId = $money['currency'];

        if ($amount !== null && abs((float)($deal['OPPORTUNITY'] ?? 0) - $amount) > 0.01) {
            $fields['OPPORTUNITY'] = $amount;
        }

        if ($currencyId !== null && (string)($deal['CURRENCY_ID'] ?? '') !== $currencyId) {
            $fields['CURRENCY_ID'] = $currencyId;
        }

        if ($responsibleId > 0 && (int)($deal['ASSIGNED_BY_ID'] ?? 0) !== $responsibleId) {
            $fields['ASSIGNED_BY_ID'] = $responsibleId;
        }

        return $fields;
    }

    /**
     * Нормализует значение привязки к сделке.
     *
     * @param mixed $value Значение свойства.
     *
     * @return int
     */
    private static function normalizeDealId($value): int
    {
        if (is_array($value)) {
            if (isset($value['VALUE'])) {
                return static::normalizeDealId($value['VALUE']);
            }

            foreach ($value as $item) {
                $dealId = static::normalizeDealId($item);
                if ($dealId > 0) {
                    return $dealId;
                }
            }

            return 0;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }

        if (ctype_digit($value)) {
            return (int)$value;
        }

        if (preg_match('/(?:D|DEAL)_(\d+)$/i', $value, $matches)) {
            return (int)$matches[1];
        }

        if (preg_match('/(\d+)$/', $value, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }

    /**
     * Нормализует значение свойства типа Деньги.
     *
     * Свойство инфоблока с пользовательским типом Деньги хранит значение в формате сумма|валюта,
     * например 1500|RUB. Для совместимости также поддерживается обычное числовое значение.
     *
     * @param mixed $value Значение свойства.
     *
     * @return array{amount: float|null, currency: string|null}
     */
    private static function normalizeMoney($value): array
    {
        if (is_array($value)) {
            if (isset($value['VALUE'])) {
                return static::normalizeMoney($value['VALUE']);
            }

            foreach ($value as $item) {
                $money = static::normalizeMoney($item);
                if ($money['amount'] !== null) {
                    return $money;
                }
            }

            return [
                'amount' => null,
                'currency' => null,
            ];
        }

        $parts = explode('|', (string)$value, 2);

        return [
            'amount' => static::normalizeAmount($parts[0] ?? null),
            'currency' => static::normalizeCurrencyId($parts[1] ?? null),
        ];
    }

    /**
     * Нормализует значение суммы.
     *
     * @param mixed $value Значение суммы.
     *
     * @return float|null
     */
    private static function normalizeAmount($value): ?float
    {
        if (is_array($value)) {
            if (isset($value['VALUE'])) {
                return static::normalizeAmount($value['VALUE']);
            }

            foreach ($value as $item) {
                $amount = static::normalizeAmount($item);
                if ($amount !== null) {
                    return $amount;
                }
            }

            return null;
        }

        $value = trim(str_replace([' ', ','], ['', '.'], (string)$value));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (float)$value;
    }

    /**
     * Нормализует код валюты.
     *
     * @param mixed $value Код валюты.
     *
     * @return string|null
     */
    private static function normalizeCurrencyId($value): ?string
    {
        if (is_array($value)) {
            if (isset($value['VALUE'])) {
                return static::normalizeCurrencyId($value['VALUE']);
            }

            foreach ($value as $item) {
                $currencyId = static::normalizeCurrencyId($item);
                if ($currencyId !== null) {
                    return $currencyId;
                }
            }

            return null;
        }

        $currencyId = strtoupper(trim((string)$value));

        return $currencyId !== '' ? $currencyId : null;
    }

    /**
     * Форматирует значение для свойства инфоблока типа Деньги.
     *
     * @param float|null $amount Сумма сделки.
     * @param string|null $currencyId Код валюты сделки.
     *
     * @return string
     */
    private static function formatMoneyValue(?float $amount, ?string $currencyId): string
    {
        if ($amount === null) {
            return '';
        }

        if ($currencyId === null) {
            $currencyId = 'RUB';
        }

        return $amount . '|' . $currencyId;
    }

    /**
     * Нормализует значение ответственного пользователя.
     *
     * @param mixed $value Значение пользователя.
     *
     * @return int
     */
    private static function normalizeUserId($value): int
    {
        if (is_array($value)) {
            if (isset($value['VALUE'])) {
                return static::normalizeUserId($value['VALUE']);
            }

            foreach ($value as $item) {
                $userId = static::normalizeUserId($item);
                if ($userId > 0) {
                    return $userId;
                }
            }

            return 0;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }

        if (ctype_digit($value)) {
            return (int)$value;
        }

        if (preg_match('/user_(\d+)$/i', $value, $matches)) {
            return (int)$matches[1];
        }

        if (preg_match('/(\d+)$/', $value, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }

    /**
     * Возвращает название CRM-события по типу события инфоблока.
     *
     * @param string $eventType Тип события.
     *
     * @return string
     */
    private static function getDealEventNameByType(string $eventType): string
    {
        if ($eventType === 'add') {
            return Loc::getMessage('OTUS_MAIN_REQUEST_DEAL_EVENT_ADD_NAME');
        }

        return Loc::getMessage('OTUS_MAIN_REQUEST_DEAL_EVENT_UPDATE_NAME');
    }

    /**
     * Возвращает текстовый список обновленных полей.
     *
     * @param array<string, mixed> $fields Обновленные поля сделки.
     *
     * @return string
     */
    private static function getFieldNamesText(array $fields): string
    {
        $names = [];
        foreach (array_keys($fields) as $fieldName) {
            if ($fieldName === 'OPPORTUNITY') {
                $names[] = Loc::getMessage('OTUS_MAIN_REQUEST_DEAL_FIELD_AMOUNT');
            }

            if ($fieldName === 'CURRENCY_ID') {
                $names[] = Loc::getMessage('OTUS_MAIN_REQUEST_DEAL_FIELD_CURRENCY');
            }

            if ($fieldName === 'ASSIGNED_BY_ID') {
                $names[] = Loc::getMessage('OTUS_MAIN_REQUEST_DEAL_FIELD_RESPONSIBLE');
            }
        }

        return implode(', ', $names);
    }

    /**
     * Добавляет запись в историю сделки.
     *
     * @param int $dealId ID сделки.
     * @param string $eventName Название события.
     * @param string $eventText Текст события.
     *
     * @return void
     */
    private static function addDealEvent(int $dealId, string $eventName, string $eventText): void
    {
        if ($dealId <= 0 || $eventName === '' || $eventText === '' || !class_exists('CCrmEvent')) {
            return;
        }

        $crmEvent = new \CCrmEvent();
        $crmEvent->Add(
            [
                'ENTITY_TYPE' => 'DEAL',
                'ENTITY_ID' => $dealId,
                'EVENT_ID' => 'INFO',
                'EVENT_NAME' => $eventName,
                'EVENT_TEXT_1' => $eventText,
                'CREATED_BY_ID' => static::getCurrentUserId(),
            ],
            false
        );
    }

    /**
     * Возвращает ID текущего пользователя.
     *
     * @return int
     */
    private static function getCurrentUserId(): int
    {
        global $USER;
        if (is_object($USER) && method_exists($USER, 'GetID')) {
            $userId = (int)$USER->GetID();
            if ($userId > 0) {
                return $userId;
            }
        }

        return 1;
    }
}
