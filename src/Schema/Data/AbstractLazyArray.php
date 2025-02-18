<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

use ArrayAccess;
use Countable;
use JsonSerializable;
use IteratorAggregate;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_map;
use function array_walk_recursive;
use function is_string;

/**
 * Represents an array value retrieved from the database.
 * Initially, the value is a string that parsed into an array when it's accessed as an array or iterated over.
 *
 * @template-implements ArrayAccess<array-key, mixed>
 * @template-implements IteratorAggregate<array-key, mixed>
 */
abstract class AbstractLazyArray implements ArrayAccess, Countable, JsonSerializable, IteratorAggregate, LazyArrayInterface
{
    use LazyArrayTrait;

    /**
     * @var array|string $value The array value that can be represented as:
     * - a string that can be parsed into an array;
     * - an array that is already parsed and typecasted.
     */
    protected array|string $value;

    /**
     * Parses the string retrieved value from the database into an array.
     *
     * @param string $value The string retrieved value from the database that can be parsed into an array.
     *
     * @return array|null The parsed array or `null` if the string value cannot be parsed.
     */
    abstract protected function parse(string $value): array|null;

    /**
     * @param string $value The string retrieved value from the database that can be parsed into an array.
     * @param ColumnInterface|null $column The column information. This is used to typecast values.
     * @param int $dimension The number of indices needed to select an element.
     *
     * @psalm-param positive-int $dimension
     */
    public function __construct(
        string $value,
        private readonly ColumnInterface|null $column = null,
        private readonly int $dimension = 1,
    ) {
        $this->value = $value;
    }

    /**
     * Typecasts the array values to PHP types according to the column information.
     *
     * @param array $value The array to typecast.
     *
     * @return array The typecasted array.
     */
    protected function phpTypecast(array $value): array
    {
        if ($this->column === null || $this->column->getType() === ColumnType::STRING) {
            return $value;
        }

        if ($this->dimension === 1 && $this->column->getType() !== ColumnType::JSON) {
            return array_map($this->column->phpTypecast(...), $value);
        }

        array_walk_recursive($value, function (string|null &$val): void {
            /** @psalm-suppress PossiblyNullReference */
            $val = $this->column->phpTypecast($val);
        });

        return $value;
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
                throw new InvalidConfigException('Array value must be a valid string representation.');
            }

            $this->value = $this->phpTypecast($value);
        }
    }
}
