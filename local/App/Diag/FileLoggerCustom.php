<?php

namespace App\Diag;

use Bitrix\Main\Diag\FileLogger;

class FileLoggerCustom extends FileLogger
{
    /**
     * @param string $level
     * @param string $message
     * @return void
     */
    protected function logMessage(string $level, string $message): void
    {
        $lines = preg_split("/\r\n|\r|\n/", $message);
        $lines = array_map(static function ($line) {
            $line = (string)$line;
            if ($line === '')
            {
                return $line;
            }

            return (str_starts_with($line, 'OTUS ')) ? $line : ('OTUS ' . $line);
        }, $lines);

        $message = implode(PHP_EOL, $lines);

        parent::logMessage($level, $message);
    }
}