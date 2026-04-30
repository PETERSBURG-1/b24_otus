<?php

namespace Otus\Main\Service;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Сервис обработки компании по ИНН: DADATA и компания CRM.
 */
class CompanyOrderService
{
    /**
     * Ищет организацию в DADATA, затем находит или создает компанию CRM.
     *
     * @param string $inn ИНН заказчика.
     * @param int $responsibleId ID ответственного пользователя.
     * @return array Результат обработки.
     * @throws SystemException
     */
    public function process(string $inn, int $responsibleId = 0): array
    {
        $responsibleId = $this->getResponsibleId($responsibleId);

        $dadataClient = new DadataClient();
        $party = $dadataClient->findCompanyByInn($inn);
        if (empty($party)) {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_COMPANY_ORDER_NOT_FOUND'));
        }

        $companyId = (new CrmCompanyService())->findOrCreateByParty($party, $responsibleId);
        $companyTitle = (string)($party['value'] ?? $party['data']['name']['short_with_opf'] ?? '');

        return [
            'COMPANY_ID' => $companyId,
            'COMPANY_TITLE' => $companyTitle,
        ];
    }

    /**
     * Определяет ответственного пользователя.
     *
     * @param int $responsibleId ID ответственного из параметра активити.
     * @return int
     */
    private function getResponsibleId(int $responsibleId): int
    {
        if ($responsibleId > 0) {
            return $responsibleId;
        }

        global $USER;
        if (is_object($USER) && method_exists($USER, 'GetID')) {
            $currentUserId = (int)$USER->GetID();
            if ($currentUserId > 0) {
                return $currentUserId;
            }
        }

        return 1;
    }
}
