<?php

namespace Rexp\Form\Application\DTO;

/**
 * DTO-команда сохранения формы.
 */
final class SaveFormCommand
{
    /**
     * Инициализирует объект и его зависимости.
     *
     * @param int $id
     * @param string $name
     * @param string $code
     * @param bool $active
     * @param bool $archived
     * @param array $schema
     * @param int $userId
     * @param bool $createVersion
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly bool $active,
        public readonly bool $archived,
        public readonly array $schema,
        public readonly int $userId,
        public readonly bool $createVersion,
    ) {
    }
}
