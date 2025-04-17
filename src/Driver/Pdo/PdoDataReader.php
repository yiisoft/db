<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use Closure;
use Countable;
use Iterator;
use PDO;
use PDOStatement;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Query\DataReaderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function is_string;

/**
 * Provides an abstract way to read data from a database.
 *
 * A data reader is an object that can be used to read a forward-only stream of rows from a database.
 *
 * It's typically used in combination with a command object, such as a {@see \Yiisoft\Db\Command\AbstractCommand},
 * to execute a SELECT statement and read the results.
 *
 * The class provides methods for accessing the data returned by the query.
 *
 * @psalm-import-type IndexBy from QueryInterface
 * @psalm-import-type ResultCallbackOne from QueryInterface
 */
final class PdoDataReader implements DataReaderInterface
{
    /** @psalm-var IndexBy|null $indexBy */
    private Closure|string|null $indexBy = null;
    private int $index = 0;
    /** @psalm-var ResultCallbackOne|null $resultCallback */
    private Closure|null $resultCallback = null;
    private array|false $row;

    /**
     * @param PDOStatement $statement The PDO statement object that contains the result of the query.
     */
    public function __construct(private readonly PDOStatement $statement)
    {
        /** @var array|false */
        $this->row = $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function __destruct()
    {
        $this->statement->closeCursor();
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
        if ($this->index === 0) {
            return;
        }

        throw new InvalidCallException('DataReader cannot rewind. It is a forward-only reader.');
    }

    public function key(): int|string|null
    {
        if ($this->indexBy === null) {
            return $this->index;
        }

        if ($this->row === false) {
            return null;
        }

        if (is_string($this->indexBy)) {
            return (string) $this->row[$this->indexBy];
        }

        return ($this->indexBy)($this->row);
    }

    public function current(): array|object|false
    {
        if ($this->resultCallback === null || $this->row === false) {
            return $this->row;
        }

        return ($this->resultCallback)($this->row);
    }

    /**
     * Moves the internal pointer to the next row.
     *
     * This method is required by the interface {@see Iterator}.
     */
    public function next(): void
    {
        /** @var array|false */
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

    public function indexBy(Closure|string|null $indexBy): static
    {
        $this->indexBy = $indexBy;
        return $this;
    }

    public function resultCallback(Closure|null $resultCallback): static
    {
        $this->resultCallback = $resultCallback;
        return $this;
    }
}
