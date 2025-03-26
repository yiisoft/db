<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\Data\JsonLazyArray;

use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Represents a JSON column with lazy parsing values retrieved from the database.
 *
 * @see JsonColumn for a JSON column with eager parsing values retrieved from the database.
 */
final class JsonLazyColumn extends AbstractJsonColumn
{
    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value)) {
            return match ($value[0]) {
                '[', '{' => new JsonLazyArray($value),
                default => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            };
        }

        return $value;
    }
}
