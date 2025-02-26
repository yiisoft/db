<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Represents a JSON array value retrieved from the database.
 * Initially, the value is a string that parsed into an array when it's accessed as an array or iterated over.
 */
final class LazyArray extends AbstractLazyArray
{
    protected function parse(string $value): array|null
    {
        /** @var array|null */
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
