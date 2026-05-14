<?php

namespace Rexp\Form\Domain\ValueObject;

use InvalidArgumentException;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * Value object символьного кода формы.
 */
final class FormCode
{
    private string $value;

    /**
     * Инициализирует объект и его зависимости.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('~[^a-z0-9_]+~', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            throw new InvalidArgumentException(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_LIB_DOMAIN_VALUEOBJECT_FORMCODE_001'));
        }

        $this->value = $normalized;
    }

    /**
     * Возвращает значение value object.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
