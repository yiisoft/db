<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Data;

use Countable;
use Iterator;
use PDO;
use PDOStatement;
use Yiisoft\Db\Driver\Pdo\PdoCommandInterface;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidParamException;

/**
 * Provides an abstract way to read data from a database.
 *
 * A data reader is an object that can be used to read a forward-only stream of rows from a database.
 *
 * It's typically used in combination with a command object, such as a {@see \Yiisoft\Db\Command\AbstractCommand},
 * to execute a SELECT statement and read the results.
 *
 * The class provides methods for accessing the data returned by the query.
 */
final class DataReader implements DataReaderInterface
{
    private int $index = -1;
    private mixed $row;
    private PDOStatement $statement;

    /**
     * @throws InvalidParamException If the PDOStatement is null.
     */
    public function __construct(PdoCommandInterface $command)
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
     * This method is required by the interface {@see Countable}.
     *
     * Note, most DBMS mayn't give a meaningful count. In this case, use "SELECT COUNT(*) FROM tableName" to obtain the
     * number of rows.
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
     * @throws InvalidCallException If the data reader isn't at the beginning.
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
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * Returns the current row.
     *
     * This method is required by the interface {@see Iterator}.
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
     */
    public function valid(): bool
    {
        return $this->row !== false;
    }
}
