<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use Yiisoft\Db\Exception\InvalidArgumentException;
use function addslashes;
use function is_float;
use function mb_strrpos;
use function mb_strtolower;
use function mb_substr;
use function preg_replace;
use function rtrim;
use function str_replace;
use function trim;

/**
 * Short implementation of StringHelper from Yii2.
 */
final class StringHelper
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
     * @link http://www.php.net/manual/en/function.basename.php
     */
    public static function baseName(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        $position = mb_strrpos($path, '/');

        if ($position !== false) {
            return mb_substr($path, $position + 1);
        }

        return $path;
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

    public static function toUuid(string $blobString)
    {
        if (self::isValidUuid($blobString)) {
            return $blobString;
        }

        if (strlen($blobString) === 16) {
            $hex = bin2hex($blobString);
        } elseif(strlen($blobString) === 32 && self::isValidHexUuid($blobString)) {
            $hex = $blobString;
        } else {
            throw new InvalidArgumentException('Length of source data is should be 16 or 32 bytes.');
        }

        return
            substr($hex, 0, 8) . '-' .
            substr($hex, 8, 4) . '-' .
            substr($hex, 12, 4) . '-' .
            substr($hex, 16, 4) . '-' .
            substr($hex, 20)
        ;
    }

    public static function uuidToBlob(string $uuidString): string
    {
        if (!self::isValidUuid($uuidString)) {
            throw new InvalidArgumentException('Incorrect UUID.');
        }

        return (string) hex2bin(str_replace('-', '', $uuidString));
    }

    public static function isValidUuid(string $uuidString): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuidString);
    }

    public static function isValidHexUuid(string $uuidString): bool
    {
        return (bool) preg_match('/^[0-9a-f]{32}$/i', $uuidString);
    }
}
