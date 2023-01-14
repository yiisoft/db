<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use Yiisoft\Db\Exception\InvalidArgumentException;

final class NumericHelper
{
    /**
     * Returns string representation of a number value without thousands separators and with dot as decimal separator.
     *
     * @param bool|float|int|string $value
     *
     * @throws InvalidArgumentException if value is not scalar.
     *
     * @return string
     */
    public static function normalize($value): string
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_scalar($value)) {
            $type = gettype($value);
            throw new InvalidArgumentException("Value must be scalar. $type given.");
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } else {
            $value = (string)$value;
        }
        $value = str_replace([' ', ','], ['', '.'], $value);
        return preg_replace('/\.(?=.*\.)/', '', $value);
    }
}
