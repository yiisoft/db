<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

/**
 * Represents the schema for an array column with eager parsing values retrieved from the database.
 */
class ArrayColumnSchema extends ArrayColumnLazySchema
{
    /** @psalm-suppress MethodSignatureMismatch */
    public function phpTypecast(mixed $value): array|null
    {
        $value = parent::phpTypecast($value);

        if ($value === null) {
            return null;
        }

        return $value->toArray();
    }
}
