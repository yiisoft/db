<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests\Util;

/**
 * TraversableObject
 *
 * Object that implements `\Traversable` and `\Countable`, but counting throws an exception;
 * Used for testing support for traversable objects instead of arrays.
 */
class TraversableObject implements \Iterator, \Countable
{
    protected $data;

    private $position = 0;

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

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->data[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return array_key_exists($this->position, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }
}
