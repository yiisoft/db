<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use JsonSerializable;
use IteratorAggregate;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function is_string;

/**
 * Represents an array value of a structured type retrieved from the database.
 * Initially, the value is a string that parsed into an array when it's accessed as an array or iterated over.
 *
 * @template-implements ArrayAccess<array-key, mixed>
 * @template-implements IteratorAggregate<array-key, mixed>
 */
abstract class AbstractStructuredLazyArray implements ArrayAccess, Countable, JsonSerializable, IteratorAggregate, LazyArrayInterface
{
    use LazyArrayTrait;

    /**
     * @var array|string The structured array value.
     */
    private array|string $value;

    /**
     * @param string $value The string retrieved value from the database that can be parsed into an array.
     * @param ColumnInterface[] $columns The structured type columns that are used for value normalization and type
     * casting.
     *
     * @psalm-param array<string, ColumnInterface> $columns
     */
    public function __construct(
        string $value,
        private readonly array $columns = [],
    ) {
        $this->value = $value;
    }

    /**
     * Parses the string retrieved value from the database into an array.
     *
     * @param string $value The string retrieved value from the database that can be parsed into an array.
     *
     * @return array|null The parsed array or `null` if the string value cannot be parsed.
     */
    abstract protected function parse(string $value): ?array;

    /**
     * Typecasts the structured values to PHP types according to the column schemas information.
     *
     * @psalm-suppress MixedArrayTypeCoercion
     */
    protected function phpTypecast(array $value): array
    {
        if (empty($this->columns)) {
            return $value;
        }

        $fields = [];
        $columnNames = array_keys($this->columns);

        foreach ($value as $columnName => $item) {
            $columnName = $columnNames[$columnName] ?? $columnName;

            if (isset($this->columns[$columnName])) {
                $fields[$columnName] = $this->columns[$columnName]->phpTypecast($item);
            } else {
                $fields[$columnName] = $item;
            }
        }

        return $fields;
    }

    /**
     * Prepares the value to be used as an array or throws an exception if it's impossible.
     *
     * @psalm-assert array $this->value
     */
    protected function prepareValue(): void
    {
        if (is_string($this->value)) {
            $value = $this->parse($this->value);

            if ($value === null) {
                throw new InvalidArgumentException('Structured value must be a valid string representation.');
            }

            $this->value = $this->phpTypecast($value);
        }
    }
}
