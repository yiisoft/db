<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

/**
 * TraversableObject
 *
 * Object that implements `\Traversable` and `\Countable`, but counting throws an exception;
 * Used for testing support for traversable objects instead of arrays.
 */
class TraversableObject implements \Iterator, \Countable
{
    protected array $data = [];

    private int $position = 0;

    public function __construct(array $array)
    {
        $this->data = $array;
    }

    /**
     * @throws \Exception
     */
    public function count()
    {
        throw new \Exception('Count called on object that should only be traversed.');
    }

    public function current()
    {
        return $this->data[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return array_key_exists($this->position, $this->data);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}
