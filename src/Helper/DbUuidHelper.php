<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use Yiisoft\Db\Exception\InvalidArgumentException;

use function bin2hex;
use function hex2bin;
use function preg_match;
use function str_replace;

final class DbUuidHelper
{
    public static function toUuid(string $blobString): string
    {
        if (self::isValidUuid($blobString)) {
            return $blobString;
        }

        if (strlen($blobString) === 16) {
            $hex = bin2hex($blobString);
        } elseif (strlen($blobString) === 32 && self::isValidHexUuid($blobString)) {
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

    public static function isValidUuid(string $uuidString): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuidString);
    }

    public static function isValidHexUuid(string $uuidString): bool
    {
        return (bool) preg_match('/^[0-9a-f]{32}$/i', $uuidString);
    }

    public static function uuidToBlob(string $uuidString): string
    {
        if (!self::isValidUuid($uuidString)) {
            throw new InvalidArgumentException('Incorrect UUID.');
        }

        return (string) hex2bin(str_replace('-', '', $uuidString));
    }
}
