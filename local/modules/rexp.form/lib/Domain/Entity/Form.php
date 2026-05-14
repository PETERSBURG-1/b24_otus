<?php

namespace Rexp\Form\Domain\Entity;

/**
 * Доменная сущность формы.
 */
final class Form
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
     * @param int $createdBy
     * @param int $updatedBy
     * @param string $createdAt
     * @param string $updatedAt
     */
    public function __construct(
        private int $id,
        private string $name,
        private string $code,
        private bool $active,
        private bool $archived,
        private array $schema,
        private int $createdBy,
        private int $updatedBy,
        private string $createdAt,
        private string $updatedAt,
    ) {
    }

    /**
     * Возвращает идентификатор формы.
     *
     * @return int
     */
    public function getId(): int { return $this->id; }
    /**
     * Возвращает название формы.
     *
     * @return string
     */
    public function getName(): string { return $this->name; }
    /**
     * Возвращает символьный код формы.
     *
     * @return string
     */
    public function getCode(): string { return $this->code; }
    /**
     * Проверяет признак active.
     *
     * @return bool
     */
    public function isActive(): bool { return $this->active; }
    /**
     * Проверяет признак archived.
     *
     * @return bool
     */
    public function isArchived(): bool { return $this->archived; }
    /**
     * Возвращает схему формы.
     *
     * @return array
     */
    public function getSchema(): array { return $this->schema; }

    /**
     * Преобразует объект в массив.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'active' => $this->active,
            'archived' => $this->archived,
            'schema' => $this->schema,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
