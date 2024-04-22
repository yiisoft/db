<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use function addslashes;
use function is_float;
use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_replace;
use function trim;

/**
 * String manipulation methods.
 */
final class DbStringHelper
{
    /**
     * Returns the trailing name part of a path.
     *
     * This method is similar to the php function `basename()` except that it will treat both \ and / as directory
     * separators, independent of the operating system.
     *
     * This method was mainly created to work on php namespaces. When working with real file paths, PHP's `basename()`
     * should work fine for you.
     *
     * Note: this method isn't aware of the actual filesystem, or path components such as "..".
     *
     * @param string $path A path string.
     *
     * @return string The trailing name part of the given path.
     *
     * @link https://www.php.net/manual/en/function.basename.php
     */
    public static function baseName(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $position = mb_strrpos($path, '/');

        if ($position !== false) {
            return mb_substr($path, $position + 1);
        }

        return $path;
    }

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
        return preg_replace('/\.(?=.*\.)/', '', $value);
    }

    /**
     * Converts a PascalCase name into an ID in lowercase.
     *
     * Words in the ID may be concatenated using '_'. For example, 'PostTag' will be converted to 'post_tag'.
     *
     * @param string $input The string to be converted.
     *
     * @return string The resulting ID.
     */
    public static function pascalCaseToId(string $input): string
    {
        $separator = '_';
        $result = preg_replace('/(?<=\p{L})(?<!\p{Lu})(\p{Lu})/u', addslashes($separator) . '\1', $input);
        return mb_strtolower(trim($result, $separator));
    }
}
