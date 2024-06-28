<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use Closure;
use Countable;
use Iterator;
use PDO;
use PDOStatement;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidParamException;
use Yiisoft\Db\Query\DataReaderInterface;
use Yiisoft\Db\Query\QueryPartsInterface;

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
 * @psalm-import-type IndexBy from QueryPartsInterface
 */
final class PdoDataReader implements DataReaderInterface
{
    /** @psalm-var IndexBy $indexBy */
    private Closure|string|null $indexBy = null;
    private int $index = 0;
    private array|false $row;
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

    public function current(): array|false
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
}
