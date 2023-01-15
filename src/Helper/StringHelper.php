<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

final class StringHelper
{
    /**
     * Returns the trailing name component of a path.
     * This method is similar to the php function `basename()` except that it will
     * treat both \ and / as directory separators, independent of the operating system.
     * This method was mainly created to work on php namespaces. When working with real
     * file paths, PHP's `basename()` should work fine for you.
     * Note: this method is not aware of the actual filesystem, or path components such as "..".
     *
     * @param string $path A path string.
     *
     * @return string The trailing name component of the given path.
     *
     * @see http://www.php.net/manual/en/function.basename.php
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
     * Returns string representation of a number value without thousands separators and with dot as decimal separator.
     *
     * @param float|string $value
     *
     * @return string
     */
    public static function normalizeFloat(float|string $value): string
    {
        if (is_float($value)) {
            $value = (string)$value;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);
        return preg_replace('/\.(?=.*\.)/', '', $value);
    }

    /**
     * Converts a PascalCase name into an ID in lowercase.
     * Words in the ID may be concatenated using '_'.
     * For example, 'PostTag' will be converted to 'post_tag'.
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
