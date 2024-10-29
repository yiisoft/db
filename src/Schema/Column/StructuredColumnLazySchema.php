<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\Data\LazyArrayJson;

/**
 * Represents the schema for a structured column with lazy parsing values retrieved from the database.
 *
 * @see StructuredColumnSchema for a structured column with eager parsing values retrieved from the database.
 */
class StructuredColumnLazySchema extends StructuredColumnSchema
{
    /**
     * @param string|null $value The string retrieved value from the database that can be parsed into an array.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): null|object
    {
        if ($value === null) {
            return null;
        }

        return new LazyArrayJson($value);
    }
}
