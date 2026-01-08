<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use JsonException;

use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Represents a JSON column with eager parsing values retrieved from the database.
 *
 * @see JsonLazyColumn for a JSON column with lazy parsing values retrieved from the database.
 */
final class JsonColumn extends AbstractJsonColumn
{
    /**
     * @throws JsonException
     */
    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value)) {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return $value;
    }
}
