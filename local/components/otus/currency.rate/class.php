<?php

use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Компонент otus:currency.rate.
 *
 * Выводит курс выбранной валюты из справочника валют.
 */
class OtusCurrencyRateComponent extends CBitrixComponent
{
    /**
     * Подготавливает параметры компонента.
     *
     * @param array $arParams Параметры компонента.
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['CURRENCY'] = strtoupper(trim((string)($arParams['CURRENCY'] ?? 'USD')));

        return $arParams;
    }

    /**
     * Выполняет компонент.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        Loc::loadMessages(__FILE__);

        if (!Loader::includeModule('currency')) {
            ShowError(Loc::getMessage('OTUS_CURRENCY_RATE_ERROR_MODULE'));
            return;
        }

        $currencyCode = $this->resolveCurrencyCode($this->arParams['CURRENCY']);
        $currency = $this->getCurrency($currencyCode);
        $baseCurrencyCode = $this->getBaseCurrencyCode();

        if ($currency === null || $baseCurrencyCode === '') {
            ShowError(Loc::getMessage('OTUS_CURRENCY_RATE_ERROR_NOT_FOUND'));
            return;
        }

        $amountCount = (int)$currency['AMOUNT_CNT'];
        $amount = (float)$currency['AMOUNT'];
        $isBaseCurrency = $currency['BASE'] === 'Y';

        if ($isBaseCurrency) {
            $amountCount = 1;
            $amount = 1;
        }

        $this->arResult = [
            'CURRENCY' => (string)$currency['CURRENCY'],
            'CURRENCY_NAME' => (string)($currency['FULL_NAME'] ?: $currency['CURRENCY']),
            'BASE_CURRENCY' => $baseCurrencyCode,
            'AMOUNT_CNT' => $amountCount,
            'AMOUNT' => $amount,
            'IS_BASE_CURRENCY' => $isBaseCurrency,
            'RATE_TEXT' => $this->buildRateText($amountCount, (string)$currency['CURRENCY'], $amount, $baseCurrencyCode),
        ];

        $this->includeComponentTemplate();
    }

    /**
     * Возвращает код базовой валюты.
     *
     * @return string
     */
    protected function getBaseCurrencyCode(): string
    {
        $baseCurrency = CurrencyTable::getRow([
            'select' => ['CURRENCY'],
            'filter' => ['=BASE' => 'Y'],
        ]);

        return (string)($baseCurrency['CURRENCY'] ?? '');
    }

    /**
     * Возвращает данные валюты по коду.
     *
     * @param string $currencyCode Код валюты.
     *
     * @return array|null
     */
    protected function getCurrency(string $currencyCode): ?array
    {
        $currency = CurrencyTable::getRow([
            'select' => [
                'CURRENCY',
                'AMOUNT_CNT',
                'AMOUNT',
                'BASE',
                'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME',
            ],
            'filter' => ['=CURRENCY' => $currencyCode],
        ]);

        return is_array($currency) ? $currency : null;
    }

    /**
     * Возвращает корректный код валюты.
     *
     * @param string $currencyCode Код валюты из параметров.
     *
     * @return string
     */
    protected function resolveCurrencyCode(string $currencyCode): string
    {
        $currency = $this->getCurrency($currencyCode);
        if ($currency !== null) {
            return $currencyCode;
        }

        return $this->getBaseCurrencyCode();
    }

    /**
     * Формирует строку курса для вывода.
     *
     * @param int $amountCount Номинал валюты.
     * @param string $currencyCode Код выбранной валюты.
     * @param float $amount Курс к базовой валюте.
     * @param string $baseCurrencyCode Код базовой валюты.
     *
     * @return string
     */
    protected function buildRateText(int $amountCount, string $currencyCode, float $amount, string $baseCurrencyCode): string
    {
        return $amountCount
            . ' '
            . $currencyCode
            . ' = '
            . $this->formatNumber($amount)
            . ' '
            . $baseCurrencyCode;
    }

    /**
     * Форматирует число для вывода.
     *
     * @param float $value Значение для форматирования.
     *
     * @return string
     */
    protected function formatNumber(float $value): string
    {
        $formattedValue = number_format($value, 4, '.', '');

        return rtrim(rtrim($formattedValue, '0'), '.');
    }
}
