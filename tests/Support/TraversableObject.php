<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Countable;
use Exception;
use Iterator;

/**
 * TraversableObject
 *
 * Object that implements `Traversable` and `Countable`, but counting throws an exception;
 * Used for testing support for traversable objects instead of arrays.
 */
final class TraversableObject implements Iterator, Countable
{
    private int $position = 0;

    public function __construct(protected array $data)
    {
    }

    /**
     * @throws Exception
     */
    public function count(): int
    {
        throw new Exception('Count called on object that should only be traversed.');
    }

    public function current(): mixed
    {
        return $this->data[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->data);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
