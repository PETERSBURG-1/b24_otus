<?php

namespace Otus\Main\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Сервис создания и поиска компаний CRM по данным DADATA.
 */
class CrmCompanyService
{
    /**
     * Находит существующую компанию по ИНН или создает новую компанию.
     *
     * @param array $party Данные организации из DADATA.
     * @param int $responsibleId ID ответственного пользователя.
     * @return int ID компании CRM.
     * @throws SystemException
     */
    public function findOrCreateByParty(array $party, int $responsibleId): int
    {
        if (!Loader::includeModule('crm')) {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_CRM_COMPANY_MODULE_ERROR'));
        }

        $fields = $this->prepareCompanyFields($party, $responsibleId);
        $inn = (string)($fields['INN'] ?? '');

        $companyId = $this->findCompanyIdByInn($inn);
        if ($companyId > 0) {
            return $companyId;
        }

        $companyId = $this->findCompanyIdByTitle($fields['TITLE']);
        if ($companyId > 0) {
            $this->createRequisite($companyId, $party);
            return $companyId;
        }

        return $this->createCompany($fields, $party);
    }

    /**
     * Подготавливает поля компании CRM из данных DADATA.
     *
     * @param array $party Данные организации из DADATA.
     * @param int $responsibleId ID ответственного пользователя.
     * @return array
     * @throws SystemException
     */
    private function prepareCompanyFields(array $party, int $responsibleId): array
    {
        $data = is_array($party['data'] ?? null) ? $party['data'] : [];
        $name = is_array($data['name'] ?? null) ? $data['name'] : [];
        $address = is_array($data['address'] ?? null) ? $data['address'] : [];

        $title = (string)($name['short_with_opf'] ?? $party['value'] ?? $name['full_with_opf'] ?? '');
        if ($title === '') {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_CRM_COMPANY_EMPTY_TITLE'));
        }

        $fields = [
            'TITLE' => $title,
            'OPENED' => 'Y',
            'COMPANY_TYPE' => 'CUSTOMER',
            'ASSIGNED_BY_ID' => $responsibleId > 0 ? $responsibleId : 1,
            'INN' => (string)($data['inn'] ?? ''),
        ];

        $addressValue = (string)($address['value'] ?? $address['unrestricted_value'] ?? '');
        if ($addressValue !== '') {
            $fields['ADDRESS'] = $addressValue;
        }

        return $fields;
    }

    /**
     * Ищет компанию по ИНН в реквизитах CRM.
     *
     * @param string $inn ИНН организации.
     * @return int
     */
    private function findCompanyIdByInn(string $inn): int
    {
        if ($inn === '' || !class_exists('\Bitrix\Crm\EntityRequisite')) {
            return 0;
        }

        try {
            $requisite = new \Bitrix\Crm\EntityRequisite();
            $result = $requisite->getList([
                'select' => ['ENTITY_ID'],
                'filter' => [
                    '=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                    '=RQ_INN' => $inn,
                ],
                'order' => ['ID' => 'DESC'],
                'limit' => 1,
            ]);

            if ($row = $result->fetch()) {
                return (int)$row['ENTITY_ID'];
            }
        } catch (\Throwable $exception) {
            return 0;
        }

        return 0;
    }

    /**
     * Ищет компанию по названию.
     *
     * @param string $title Название компании.
     * @return int
     */
    private function findCompanyIdByTitle(string $title): int
    {
        if ($title === '' || !class_exists('\CCrmCompany')) {
            return 0;
        }

        $row = \CCrmCompany::GetListEx(
            [],
            [
                '=TITLE' => $title,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        )->Fetch();

        return (int)($row['ID'] ?? 0);
    }

    /**
     * Создает компанию CRM и реквизиты компании.
     *
     * @param array $fields Поля компании.
     * @param array $party Данные организации из DADATA.
     * @return int
     * @throws SystemException
     */
    private function createCompany(array $fields, array $party): int
    {
        $crmFields = $fields;
        unset($crmFields['INN']);

        $company = new \CCrmCompany(false);
        $companyId = (int)$company->Add($crmFields, true, [
            'DISABLE_USER_FIELD_CHECK' => true,
        ]);

        if ($companyId <= 0) {
            throw new SystemException(
                Loc::getMessage('OTUS_MAIN_CRM_COMPANY_CREATE_ERROR', [
                    '#ERROR#' => (string)$company->LAST_ERROR,
                ])
            );
        }

        $this->createRequisite($companyId, $party);

        return $companyId;
    }

    /**
     * Создает реквизиты компании по данным DADATA.
     *
     * @param int $companyId ID компании CRM.
     * @param array $party Данные организации из DADATA.
     * @return void
     */
    private function createRequisite(int $companyId, array $party): void
    {
        if ($companyId <= 0 || !class_exists('\Bitrix\Crm\EntityRequisite')) {
            return;
        }

        $data = is_array($party['data'] ?? null) ? $party['data'] : [];
        if ((string)($data['inn'] ?? '') === '') {
            return;
        }

        if ($this->companyHasRequisite($companyId, (string)$data['inn'])) {
            return;
        }

        $presetId = $this->getRequisitePresetId();
        if ($presetId <= 0) {
            return;
        }

        $name = is_array($data['name'] ?? null) ? $data['name'] : [];
        $requisiteFields = [
            'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
            'ENTITY_ID' => $companyId,
            'PRESET_ID' => $presetId,
            'NAME' => (string)($name['short_with_opf'] ?? $party['value'] ?? Loc::getMessage('OTUS_MAIN_CRM_COMPANY_REQUISITE_NAME')),
            'ACTIVE' => 'Y',
            'SORT' => 500,
            'RQ_COMPANY_NAME' => (string)($name['short_with_opf'] ?? $party['value'] ?? ''),
            'RQ_COMPANY_FULL_NAME' => (string)($name['full_with_opf'] ?? $party['unrestricted_value'] ?? ''),
            'RQ_INN' => (string)($data['inn'] ?? ''),
            'RQ_KPP' => (string)($data['kpp'] ?? ''),
            'RQ_OGRN' => (string)($data['ogrn'] ?? ''),
            'RQ_OKPO' => (string)($data['okpo'] ?? ''),
        ];

        $requisiteFields = array_filter($requisiteFields, static function ($value): bool {
            return $value !== null && $value !== '';
        });

        try {
            $requisite = new \Bitrix\Crm\EntityRequisite();
            $requisite->add($requisiteFields);
        } catch (\Throwable $exception) {
            // Компания уже создана, поэтому ошибка реквизитов не должна останавливать бизнес-процесс.
        }
    }

    /**
     * Проверяет, есть ли у компании реквизит с указанным ИНН.
     *
     * @param int $companyId ID компании CRM.
     * @param string $inn ИНН организации.
     * @return bool
     */
    private function companyHasRequisite(int $companyId, string $inn): bool
    {
        if ($companyId <= 0 || $inn === '') {
            return false;
        }

        try {
            $requisite = new \Bitrix\Crm\EntityRequisite();
            $result = $requisite->getList([
                'select' => ['ID'],
                'filter' => [
                    '=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                    '=ENTITY_ID' => $companyId,
                    '=RQ_INN' => $inn,
                ],
                'limit' => 1,
            ]);

            return (bool)$result->fetch();
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Возвращает ID первого активного шаблона реквизитов для CRM.
     *
     * @return int
     */
    private function getRequisitePresetId(): int
    {
        if (!class_exists('\Bitrix\Crm\EntityPreset')) {
            return 0;
        }

        try {
            $preset = \Bitrix\Crm\EntityPreset::getSingleInstance();
            $result = $preset->getList([
                'select' => ['ID'],
                'filter' => [
                    '=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
                    '=ACTIVE' => 'Y',
                ],
                'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
                'limit' => 1,
            ]);

            if ($row = $result->fetch()) {
                return (int)$row['ID'];
            }
        } catch (\Throwable $exception) {
            return 0;
        }

        return 0;
    }
}
