<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Data;

use PDO;
use PDOStatement;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidParamException;

final class DataReader implements DataReaderInterface
{
    private int $index = -1;
    private mixed $row;
    private PDOStatement $statement;

    public function __construct(CommandPDOInterface $command)
    {
        $statement = $command->getPDOStatement();

        if ($statement === null) {
            throw new InvalidParamException('The PDOStatement cannot be null.');
        }

        $this->statement = $statement;
    }

    /**
     * Returns the number of rows in the result set.
     *
     * This method is required by the Countable interface.
     *
     * Note, most DBMS may not give a meaningful count. In this case, use "SELECT COUNT(*) FROM tableName" to obtain the
     * number of rows.
     *
     * @return int number of rows contained in the result.
     */
    public function count(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * Resets the iterator to the initial state.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @throws InvalidCallException
     */
    public function rewind(): void
    {
        if ($this->index < 0) {
            $this->row = $this->statement->fetch(PDO::FETCH_ASSOC);
            $this->index = 0;
        } else {
            throw new InvalidCallException('DataReader cannot rewind. It is a forward-only reader.');
        }
    }

    /**
     * Returns the index of the current row.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return int the index of the current row.
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * Returns the current row.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return mixed the current row.
     */
    public function current(): mixed
    {
        return $this->row;
    }

    /**
     * Moves the internal pointer to the next row.
     *
     * This method is required by the interface {@see Iterator}.
     */
    public function next(): void
    {
        $this->row = $this->statement->fetch(PDO::FETCH_ASSOC);
        $this->index++;
    }

    /**
     * Returns whether there is a row of data at current position.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return bool whether there is a row of data at current position.
     */
    public function valid(): bool
    {
        return $this->row !== false;
    }
}
