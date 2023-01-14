<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use Yiisoft\Db\Exception\InvalidArgumentException;

final class NumericHelper
{
    /**
     * Returns string representation of a number value without thousands separators and with dot as decimal separator.
     *
     * @param float|string $value
     *
     * @return string
     */
    public static function normalize(float|string $value): string
    {
        if (is_float($value)) {
            $value = (string)$value;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);
        return preg_replace('/\.(?=.*\.)/', '', $value);
    }
}
