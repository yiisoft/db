<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use function is_float;
use function preg_match;
use function preg_replace;
use function str_replace;

/**
 * String manipulation methods.
 */
final class DbStringHelper
{
    /**
     * Returns a value indicating whether an SQL statement is for read purpose.
     *
     * @param string $sql The SQL statement.
     *
     * @return bool Whether an SQL statement is for read purpose.
     */
    public static function isReadQuery(string $sql): bool
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';

        return preg_match($pattern, $sql) === 1;
    }

    /**
     * Returns string representation of a number value without a thousand separators and with dot as decimal separator.
     *
     * @param float|string $value The number value to be normalized.
     */
    public static function normalizeFloat(float|string $value): string
    {
        if (is_float($value)) {
            $value = (string) $value;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);

        /** @var string */
        return preg_replace('/\.(?=.*\.)/', '', $value);
    }
}
