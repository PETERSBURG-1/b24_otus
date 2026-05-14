<?php


use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Публичный компонент отображения и отправки формы.
 */
class RexpFormRuntimeComponent extends CBitrixComponent
{
    /**
     * Нормализует входные параметры компонента.
     *
     * @param mixed $arParams
     *
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['FORM_CODE'] = trim((string)($arParams['FORM_CODE'] ?? ''));
        $arParams['FORM_ID'] = (int)($arParams['FORM_ID'] ?? 0);
        $arParams['CONTROLLER'] = trim((string)($arParams['CONTROLLER'] ?? 'rexp:form.Runtime'));
        $arParams['TITLE'] = trim((string)($arParams['TITLE'] ?? ''));
        $arParams['MODE'] = trim((string)($arParams['MODE'] ?? 'auto')) ?: 'auto';
        $arParams['ENTRY_ID'] = (int)($arParams['ENTRY_ID'] ?? 0);

        return $arParams;
    }

    /**
     * Подготавливает данные компонента и подключает шаблон.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule('rexp.form')) {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_RUNTIME_CLASS_001'));
            return;
        }

        if ($this->arParams['FORM_CODE'] === '' && $this->arParams['FORM_ID'] > 0) {
            try {
                $readService = ServiceLocator::getInstance()->get('rexp.form.service.read');
                if ($readService) {
                    $item = $readService->getPublicById($this->arParams['FORM_ID']);
                    $this->arParams['FORM_CODE'] = trim((string)($item['code'] ?? ''));
                }
            } catch (\Throwable $exception) {
            }
        }

        if ($this->arParams['FORM_CODE'] === '') {
            ShowError(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_RUNTIME_CLASS_002'));
            return;
        }

        $this->arResult = [
            'FORM_CODE' => $this->arParams['FORM_CODE'],
            'FORM_ID' => $this->arParams['FORM_ID'],
            'CONTROLLER' => $this->arParams['CONTROLLER'],
            'TITLE' => $this->arParams['TITLE'],
            'MODE' => $this->arParams['MODE'],
            'ENTRY_ID' => $this->arParams['ENTRY_ID'],
        ];

        $this->includeComponentTemplate();
    }
}
