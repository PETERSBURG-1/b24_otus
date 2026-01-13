<?php

namespace App\Debug;

use Bitrix\Main\Diag\Logger;
use Psr\Log\LogLevel;
final class Log
{
    private const LOGGER_ID = 'otus.Logger';

    /**
     * @param string $message
     * @param string $level
     * @return void
     */
    public static function write(string $message, string $level = LogLevel::INFO): void
    {
        $logger = Logger::create(self::LOGGER_ID);

        if (!$logger)
        {
            return;
        }

        $logger->log($level, "{date} - Host: {host} - {$message}\n");
    }

    /**
     * @param string|null $target
     * @return void
     */
    public static function clear(?string $target = null): void
    {
        if ($target === null)
        {
            return;
        }

        $root = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');

        $customFile    = $root . '/local/logs/log_custom.log';
        $exceptionFile = $root . '/local/logs/exceptions.log';

        switch ($target)
        {
            case 'all':
                if (file_exists($customFile))    { file_put_contents($customFile, ''); }
                if (file_exists($exceptionFile)) { file_put_contents($exceptionFile, ''); }
                break;

            case 'exception':
                if (file_exists($exceptionFile)) { file_put_contents($exceptionFile, ''); }
                break;

            case 'custom':
                if (file_exists($customFile)) { file_put_contents($customFile, ''); }
                break;

            default:
                break;
        }
    }
}