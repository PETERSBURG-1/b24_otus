<?php

namespace Otus\Main\Rest;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\RestException;
use Otus\Main\Model\BookTable;

Loc::loadMessages(__FILE__);

/**
 * Регистрирует и обрабатывает собственные REST-методы для сущности "Книга".
 */
class BookRest
{
    /**
     * Регистрирует REST-scope и CRUD-методы.
     *
     * @return array
     */
    public static function onRestServiceBuildDescription()
    {
        Loc::getMessage('REST_SCOPE_OTUS.BOOK');

        return [
            'otus.book' => [
                'otus.book.add' => [__CLASS__, 'add'],
                'otus.book.get' => [__CLASS__, 'get'],
                'otus.book.list' => [__CLASS__, 'getList'],
                'otus.book.update' => [__CLASS__, 'update'],
                'otus.book.delete' => [__CLASS__, 'delete'],
            ],
        ];
    }

    /**
     * Создает книгу.
     *
     * @param array $arParams Параметры REST-запроса.
     * @param mixed $navStart Параметр навигации REST.
     * @param \CRestServer $server Экземпляр REST-сервера.
     * @return array
     * @throws RestException
     */
    public static function add(array $arParams, $navStart, \CRestServer $server)
    {
        $fields = self::prepareFields($arParams);
        if (empty($fields['TITLE'])) {
            self::throwError('add', $arParams, Loc::getMessage('OTUS_MAIN_BOOK_REST_ERROR_TITLE'));
        }

        $result = BookTable::add($fields);
        if (!$result->isSuccess()) {
            self::throwError('add', $arParams, implode('; ', $result->getErrorMessages()));
        }

        $response = ['ID' => (int)$result->getId()];
        self::writeLog('add', $arParams, $response);

        return $response;
    }

    /**
     * Возвращает книгу по ID.
     *
     * @param array $arParams Параметры REST-запроса.
     * @param mixed $navStart Параметр навигации REST.
     * @param \CRestServer $server Экземпляр REST-сервера.
     * @return array
     * @throws RestException
     */
    public static function get(array $arParams, $navStart, \CRestServer $server)
    {
        $id = self::getId($arParams, 'get');
        $book = BookTable::getByPrimary($id)->fetch();

        if (!$book) {
            self::throwError(
                'get',
                $arParams,
                Loc::getMessage('OTUS_MAIN_BOOK_REST_ERROR_NOT_FOUND', ['#ID#' => $id])
            );
        }

        $response = self::formatRow($book);
        self::writeLog('get', $arParams, $response);

        return $response;
    }

    /**
     * Возвращает список книг.
     *
     * @param array $arParams Параметры REST-запроса.
     * @param mixed $navStart Параметр навигации REST.
     * @param \CRestServer $server Экземпляр REST-сервера.
     * @return array
     */
    public static function getList(array $arParams, $navStart, \CRestServer $server)
    {
        $limit = (int)($arParams['LIMIT'] ?? $arParams['limit'] ?? 50);
        $limit = max(1, min($limit, 100));
        $items = [];

        $result = BookTable::getList([
            'select' => ['*'],
            'order' => ['ID' => 'DESC'],
            'limit' => $limit,
        ]);

        while ($row = $result->fetch()) {
            $items[] = self::formatRow($row);
        }

        $response = ['items' => $items];
        self::writeLog('list', $arParams, $response);

        return $response;
    }

    /**
     * Обновляет книгу по ID.
     *
     * @param array $arParams Параметры REST-запроса.
     * @param mixed $navStart Параметр навигации REST.
     * @param \CRestServer $server Экземпляр REST-сервера.
     * @return array
     * @throws RestException
     */
    public static function update(array $arParams, $navStart, \CRestServer $server)
    {
        $id = self::getId($arParams, 'update');
        $fields = self::prepareFields($arParams);

        if (!$fields) {
            self::throwError('update', $arParams, Loc::getMessage('OTUS_MAIN_BOOK_REST_ERROR_FIELDS'));
        }

        if (isset($fields['TITLE']) && $fields['TITLE'] === '') {
            self::throwError('update', $arParams, Loc::getMessage('OTUS_MAIN_BOOK_REST_ERROR_TITLE'));
        }

        $fields['UPDATED_AT'] = new DateTime();
        $result = BookTable::update($id, $fields);

        if (!$result->isSuccess()) {
            self::throwError('update', $arParams, implode('; ', $result->getErrorMessages()));
        }

        $response = ['ID' => $id];
        self::writeLog('update', $arParams, $response);

        return $response;
    }

    /**
     * Удаляет книгу по ID.
     *
     * @param array $arParams Параметры REST-запроса.
     * @param mixed $navStart Параметр навигации REST.
     * @param \CRestServer $server Экземпляр REST-сервера.
     * @return array
     * @throws RestException
     */
    public static function delete(array $arParams, $navStart, \CRestServer $server)
    {
        $id = self::getId($arParams, 'delete');
        $result = BookTable::delete($id);

        if (!$result->isSuccess()) {
            self::throwError('delete', $arParams, implode('; ', $result->getErrorMessages()));
        }

        $response = ['success' => true];
        self::writeLog('delete', $arParams, $response);

        return $response;
    }

    /**
     * Подготавливает поля книги из REST-запроса.
     *
     * @param array $arParams Параметры REST-запроса.
     * @return array
     */
    private static function prepareFields(array $arParams)
    {
        $sourceFields = $arParams['FIELDS'] ?? $arParams['fields'] ?? [];
        if (!is_array($sourceFields)) {
            return [];
        }

        $fields = [];
        foreach ($sourceFields as $code => $value) {
            $code = mb_strtoupper((string)$code);
            if (in_array($code, ['TITLE', 'AUTHOR', 'DESCRIPTION'], true)) {
                $fields[$code] = is_string($value) ? trim($value) : $value;
            }
        }

        return $fields;
    }

    /**
     * Возвращает ID из REST-запроса.
     *
     * @param array $arParams Параметры REST-запроса.
     * @param string $methodName Название REST-метода.
     * @return int
     * @throws RestException
     */
    private static function getId(array $arParams, $methodName)
    {
        $id = (int)($arParams['ID'] ?? $arParams['id'] ?? 0);
        if ($id <= 0) {
            self::throwError($methodName, $arParams, Loc::getMessage('OTUS_MAIN_BOOK_REST_ERROR_ID'));
        }

        return $id;
    }

    /**
     * Форматирует строку для REST-ответа.
     *
     * @param array $row Строка из ORM.
     * @return array
     */
    private static function formatRow(array $row)
    {
        foreach ($row as $code => $value) {
            if ($value instanceof DateTime) {
                $row[$code] = $value->toString();
            }
        }

        return $row;
    }

    /**
     * Записывает входящие данные и результат обработки в лог.
     *
     * @param string $methodName Название REST-метода.
     * @param array $params Входящие параметры.
     * @param mixed $result Результат обработки.
     * @param string $error Текст ошибки.
     * @return void
     */
    private static function writeLog($methodName, array $params, $result = null, $error = '')
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $dir . '/rest_book.log',
            print_r([
                'date' => date('c'),
                'method' => $methodName,
                'params' => $params,
                'result' => $result,
                'error' => $error,
            ], true) . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * Логирует ошибку и возвращает REST-ошибку.
     *
     * @param string $methodName Название REST-метода.
     * @param array $params Входящие параметры.
     * @param string $message Текст ошибки.
     * @return void
     * @throws RestException
     */
    private static function throwError($methodName, array $params, $message)
    {
        self::writeLog($methodName, $params, null, $message);

        throw new RestException(
            $message,
            RestException::ERROR_ARGUMENT,
            \CRestServer::STATUS_OK
        );
    }
}
