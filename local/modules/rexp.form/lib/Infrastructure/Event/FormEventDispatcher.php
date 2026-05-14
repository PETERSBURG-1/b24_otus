<?php

namespace Rexp\Form\Infrastructure\Event;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Rexp\Form\Domain\Entity\Form;

/**
 * Диспетчер событий модуля конструктора форм.
 */
final class FormEventDispatcher
{
    public const MODULE_ID = 'rexp.form';

    /**
     * Отправляет событие модуля без изменения параметров.
     *
     * @param string $eventName
     * @param array $parameters
     *
     * @return void
     */
    public function dispatch(string $eventName, array $parameters = []): void
    {
        (new Event(self::MODULE_ID, $eventName, $parameters))->send();
    }

    /**
     * Отправляет событие модуля и возвращает изменённые параметры.
     *
     * @param string $eventName
     * @param array $parameters
     *
     * @return array
     */
    public function dispatchMutable(string $eventName, array $parameters = []): array
    {
        $event = new Event(self::MODULE_ID, $eventName, $parameters);
        $event->send();

        $currentParameters = $parameters;
        $errors = [];
        $canceled = false;

        foreach ($this->getEventResults($event) as $result) {
            if (!$result instanceof EventResult) {
                continue;
            }

            if ($result->getType() === EventResult::ERROR) {
                $canceled = true;
            }

            $payload = $result->getParameters();
            if (is_array($payload['parameters'] ?? null)) {
                $currentParameters = $payload['parameters'];
            } elseif (is_array($payload)) {
                foreach (['code', 'entryId', 'mode', 'values', 'uploadedFiles', 'existingFilesState', 'context', 'schema'] as $mutableKey) {
                    if (array_key_exists($mutableKey, $payload)) {
                        $currentParameters[$mutableKey] = $payload[$mutableKey];
                    }
                }
            }

            if (!empty($payload['cancel'])) {
                $canceled = true;
            }

            $errors = array_merge($errors, $this->extractErrorMessages($result));
            if (isset($payload['errors'])) {
                $errors = array_merge($errors, $this->normalizeErrors($payload['errors']));
            }
        }

        return [
            'parameters' => $currentParameters,
            'errors' => array_values(array_unique(array_filter($errors, static fn($message) => trim((string)$message) !== ''))),
            'canceled' => $canceled,
        ];
    }

    /**
     * Отправляет событие модуля с возможностью отмены операции.
     *
     * @param string $eventName
     * @param array $parameters
     *
     * @return array
     */
    public function dispatchCancelable(string $eventName, array $parameters = []): array
    {
        return $this->dispatchMutable($eventName, $parameters);
    }

    /**
     * Отправляет событие модуля для доменной формы.
     *
     * @param string $eventName
     * @param Form $form
     * @param array $extra
     *
     * @return void
     */
    public function dispatchForm(string $eventName, Form $form, array $extra = []): void
    {
        $this->dispatch($eventName, array_merge(['form' => $form->toArray()], $extra));
    }

    /**
     * Регистрирует обработчик события модуля.
     *
     * @param string $eventName
     * @param callable|array|string $handler
     *
     * @return void
     */
    public function addHandler(string $eventName, callable|array|string $handler): void
    {
        EventManager::getInstance()->addEventHandler(self::MODULE_ID, $eventName, $handler);
    }

    /**
     * Возвращает результаты обработки события.
     *
     * @param Event $event
     *
     * @return array
     */
    private function getEventResults(Event $event): array
    {
        $results = $event->getResults();

        return is_array($results) ? $results : [];
    }

    /**
     * Извлекает сообщения об ошибках из результата события.
     *
     * @param EventResult $result
     *
     * @return array
     */
    private function extractErrorMessages(EventResult $result): array
    {
        $messages = [];
        foreach ($result->getErrors() as $error) {
            if ($error instanceof Error) {
                $messages[] = (string)$error->getMessage();
                continue;
            }

            if (is_object($error) && method_exists($error, 'getMessage')) {
                $messages[] = (string)$error->getMessage();
                continue;
            }

            $messages[] = (string)$error;
        }

        return $messages;
    }

    /**
     * Нормализует список ошибок события.
     *
     * @param mixed $errors
     *
     * @return array
     */
    private function normalizeErrors(mixed $errors): array
    {
        if (is_string($errors)) {
            return [$errors];
        }

        if (!is_array($errors)) {
            return [];
        }

        $messages = [];
        foreach ($errors as $error) {
            if ($error instanceof Error) {
                $messages[] = (string)$error->getMessage();
                continue;
            }

            if (is_object($error) && method_exists($error, 'getMessage')) {
                $messages[] = (string)$error->getMessage();
                continue;
            }

            $messages[] = (string)$error;
        }

        return $messages;
    }
}
