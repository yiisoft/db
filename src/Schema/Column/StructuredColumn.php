<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\Data\StructuredLazyArray;

use function is_string;

/**
 * Represents a structured column with eager parsing values retrieved from the database.
 *
 * @see StructuredLazyColumn for a structured column with lazy parsing values retrieved from the database.
 */
final class StructuredColumn extends AbstractStructuredColumn
{
    /**
     * @param string|null $value The string retrieved value from the database that can be parsed into an array.
     */
    public function phpTypecast(mixed $value): ?array
    {
        if (is_string($value)) {
            return (new StructuredLazyArray($value, $this->getColumns()))->getValue();
        }

        return $value;
    }
}
