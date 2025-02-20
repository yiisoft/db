<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;

use function is_array;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Represents a JSON array value retrieved from the database.
 * Initially, the value is a string that parsed into an array when it's accessed as an array or iterated over.
 *
 * @template-implements ArrayAccess<array-key, mixed>
 * @template-implements IteratorAggregate<array-key, mixed>
 */
final class LazyArrayJson implements LazyArrayInterface, ArrayAccess, Countable, JsonSerializable, IteratorAggregate
{
    use LazyArrayTrait;

    protected array|string $value;

    /**
     * @param string $value The string retrieved value from the database that can be parsed into an array.
     */
    public function __construct(
        string $value
    ) {
        $this->value = $value;
    }

    /**
     * Prepares the value to be used as an array or throws an exception if it's impossible.
     *
     * @psalm-assert array $this->value
     */
    protected function prepareValue(): void
    {
        if (is_string($this->value)) {
            $value = json_decode($this->value, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($value)) {
                throw new InvalidArgumentException('JSON value must be a valid string array representation.');
            }

            $this->value = $value;
        }
    }
}
