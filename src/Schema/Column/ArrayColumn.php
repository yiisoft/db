<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\Data\LazyArray;

use function is_string;

/**
 * Represents an array column with eager parsing values retrieved from the database.
 *
 * @see ArrayLazyColumn for an array column with lazy parsing values retrieved from the database.
 */
final class ArrayColumn extends AbstractArrayColumn
{
    /**
     * @param string|null $value The string retrieved value from the database that can be parsed into an array.
     */
    public function phpTypecast(mixed $value): array|null
    {
        if (is_string($value)) {
            return (new LazyArray($value, $this->getColumn(), $this->getDimension()))->getValue();
        }

        return $value;
    }
}
