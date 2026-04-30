<?php

namespace Otus\Main\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

/**
 * Клиент для обращения к сервису DADATA.
 */
class DadataClient
{
    public const MODULE_ID = 'otus.main';
    public const OPTION_TOKEN = 'dadata_token';
    public const OPTION_SECRET = 'dadata_secret';

    private const FIND_PARTY_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';
    private const REQUEST_TIMEOUT = 10;

    private string $token;
    private string $secret;

    /**
     * Конструктор клиента DADATA.
     *
     * @param string|null $token API-ключ DADATA.
     * @param string|null $secret Секретный ключ DADATA.
     */
    public function __construct(?string $token = null, ?string $secret = null)
    {
        $this->token = trim((string)($token ?? Option::get(self::MODULE_ID, self::OPTION_TOKEN, '')));
        $this->secret = trim((string)($secret ?? Option::get(self::MODULE_ID, self::OPTION_SECRET, '')));
    }

    /**
     * Получает данные организации или ИП по ИНН через метод findById/party.
     *
     * @param string $inn ИНН организации или ИП.
     * @return array Данные первой найденной организации.
     * @throws SystemException
     */
    public function findCompanyByInn(string $inn): array
    {
        $inn = $this->normalizeInn($inn);
        if ($inn === '') {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_DADATA_EMPTY_INN'));
        }

        if (!in_array(mb_strlen($inn), [10, 12], true)) {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_DADATA_INVALID_INN'));
        }

        if ($this->token === '') {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_DADATA_EMPTY_TOKEN'));
        }

        $response = $this->sendFindPartyRequest([
            'query' => $inn,
            'count' => 1,
            'branch_type' => 'MAIN',
        ]);

        if (empty($response['suggestions'][0]) || !is_array($response['suggestions'][0])) {
            return [];
        }

        return $response['suggestions'][0];
    }

    /**
     * Нормализует ИНН перед отправкой во внешний сервис.
     *
     * @param string $inn ИНН организации или ИП.
     * @return string
     */
    public function normalizeInn(string $inn): string
    {
        return preg_replace('/\D+/', '', $inn) ?: '';
    }

    /**
     * Выполняет POST-запрос к методу DADATA findById/party.
     *
     * @param array $payload Тело запроса.
     * @return array
     * @throws SystemException
     */
    private function sendFindPartyRequest(array $payload): array
    {
        $client = new HttpClient([
            'socketTimeout' => self::REQUEST_TIMEOUT,
            'streamTimeout' => self::REQUEST_TIMEOUT,
        ]);

        $client->setHeader('Content-Type', 'application/json', true);
        $client->setHeader('Accept', 'application/json', true);
        $client->setHeader('Authorization', 'Token ' . $this->token, true);

        $rawResponse = $client->post(self::FIND_PARTY_URL, Json::encode($payload));
        if ($rawResponse === false) {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_DADATA_REQUEST_ERROR'));
        }

        $status = (int)$client->getStatus();
        if ($status < 200 || $status >= 300) {
            throw new SystemException(
                Loc::getMessage('OTUS_MAIN_DADATA_HTTP_ERROR', [
                    '#STATUS#' => $status,
                    '#RESPONSE#' => $rawResponse,
                ])
            );
        }

        $decodedResponse = Json::decode($rawResponse);
        if (!is_array($decodedResponse)) {
            throw new SystemException(Loc::getMessage('OTUS_MAIN_DADATA_RESPONSE_ERROR'));
        }

        return $decodedResponse;
    }
}
