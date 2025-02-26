<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\Data\StructuredLazyArray;

use function is_string;

/**
 * Represents a structured column with lazy parsing values retrieved from the database.
 *
 * @see StructuredColumn for a structured column with eager parsing values retrieved from the database.
 */
final class StructuredLazyColumn extends AbstractStructuredColumn
{
    /**
     * @param string|null $value The string retrieved value from the database that can be parsed into an array.
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): StructuredLazyArray|null
    {
        if (is_string($value)) {
            return new StructuredLazyArray($value, $this->getColumns());
        }

        return $value;
    }
}
