<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Otus\Main\Service\CompanyOrderService;

Loc::loadMessages(__FILE__);

/**
 * Активити получает данные компании по ИНН через DADATA и создает/находит компанию CRM.
 */
class CBPOtusCompanyByInnActivity extends BaseActivity
{
    /**
     * Конструктор активити.
     *
     * @param string $name Системное имя активити в шаблоне бизнес-процесса.
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Inn' => '',
            'ResponsibleId' => '',
            'CompanyId' => 0,
            'CompanyTitle' => '',
            'ErrorText' => '',
        ];

        $this->SetPropertiesTypes([
            'CompanyId' => ['Type' => FieldType::INT],
            'CompanyTitle' => ['Type' => FieldType::STRING],
            'ErrorText' => ['Type' => FieldType::STRING],
        ]);
    }

    /**
     * Возвращает путь к файлу активити.
     *
     * @return string
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * Выполняет активити бизнес-процесса.
     *
     * @return ErrorCollection
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();

        try {
            if (!Loader::includeModule('otus.main')) {
                throw new RuntimeException(Loc::getMessage('OTUS_COMPANY_BY_INN_ACTIVITY_MODULE_ERROR'));
            }

            $result = (new CompanyOrderService())->process(
                $this->getInn(),
                (int)$this->ResponsibleId
            );

            $this->preparedProperties['CompanyId'] = (int)$result['COMPANY_ID'];
            $this->preparedProperties['CompanyTitle'] = (string)$result['COMPANY_TITLE'];
            $this->preparedProperties['ErrorText'] = '';

            $this->log(
                Loc::getMessage('OTUS_COMPANY_BY_INN_ACTIVITY_SUCCESS_LOG', [
                    '#COMPANY_ID#' => (int)$result['COMPANY_ID'],
                ])
            );
        } catch (Throwable $exception) {
            $this->preparedProperties['CompanyId'] = 0;
            $this->preparedProperties['CompanyTitle'] = '';
            $this->preparedProperties['ErrorText'] = $exception->getMessage();

            $this->log($exception->getMessage());
            $errors->setError(new Error($exception->getMessage()));
        }

        return $errors;
    }

    /**
     * Возвращает карту параметров активити для конструктора бизнес-процессов.
     *
     * @param PropertiesDialog|null $dialog Диалог свойств активити.
     * @return array
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [
            'Inn' => [
                'Name' => Loc::getMessage('OTUS_COMPANY_BY_INN_ACTIVITY_FIELD_INN'),
                'FieldName' => 'inn',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
            'ResponsibleId' => [
                'Name' => Loc::getMessage('OTUS_COMPANY_BY_INN_ACTIVITY_FIELD_RESPONSIBLE_ID'),
                'FieldName' => 'responsible_id',
                'Type' => FieldType::INT,
                'Required' => false,
            ],
        ];
    }

    /**
     * Возвращает ИНН из параметра активити.
     *
     * @return string
     */
    private function getInn(): string
    {
        return trim((string)$this->Inn);
    }
}
