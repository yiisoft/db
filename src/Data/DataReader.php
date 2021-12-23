<?php

declare(strict_types=1);

namespace Yiisoft\Db\Data;

use Countable;
use Iterator;
use PDO;
use PDOStatement;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Exception\InvalidCallException;

use function call_user_func_array;

/**
 * DataReader represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data, call {@see read()}. The method {@see readAll()} returns all the rows in a single
 * array. Rows of data can also be read by iterating through the reader. For example,
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $reader = $command->query();
 *
 * while ($row = $reader->read()) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * foreach ($reader as $row) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * $rows = $reader->readAll();
 * ```
 *
 * Note that since DataReader is a forward-only stream, you can only traverse it once. Doing it the second time will
 * throw an exception.
 *
 * It is possible to use a specific mode of data fetching by setting {@see fetchMode}. See the
 * [PHP manual](http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php) for more details about possible
 * fetch mode.
 *
 * @property int $columnCount The number of columns in the result set. This property is read-only.
 * @property int $fetchMode Fetch mode. This property is write-only.
 * @property bool $isClosed Whether the reader is closed or not. This property is read-only.
 * @property int $rowCount Number of rows contained in the result. This property is read-only.
 */
final class DataReader implements Iterator, Countable
{
    /** @var mixed @row */
    private $row;
    private PDOStatement $statement;
    private bool $closed = false;
    private int $index = -1;

    public function __construct(Command $command)
    {
        $this->statement = $command->getPDOStatement();
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
    }

    /**
     * Binds a column to a PHP variable.
     *
     * When rows of data are being fetched, the corresponding column value will be set in the variable. Note, the fetch
     * mode must include {@see PDO::FETCH_BOUND}.
     *
     * @param int|string $column Number of the column (1-indexed) or name of the column in the result set. If using the
     * column name, be aware that the name should match the case of the column, as returned by the driver.
     * @param mixed $value Name of the PHP variable to which the column will be bound.
     * @param int|null $dataType Data type of the parameter.
     *
     * {@see http://www.php.net/manual/en/function.PDOStatement-bindColumn.php}
     */
    public function bindColumn($column, &$value, ?int $dataType = null): void
    {
        if ($dataType === null) {
            $this->statement->bindColumn($column, $value);
        } else {
            $this->statement->bindColumn($column, $value, $dataType);
        }
    }

    /**
     * Set the default fetch mode for this statement.
     *
     * @param int $mode fetch mode.
     *
     * {@see http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php}
     */
    public function setFetchMode(int $mode): void
    {
        $params = func_get_args();
        call_user_func_array([$this->statement, 'setFetchMode'], $params);
    }

    /**
     * Advances the reader to the next row in a result set.
     *
     * @return array|bool the current row, false if no more row available.
     */
    public function read()
    {
        return $this->statement->fetch();
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param int $columnIndex zero-based column index.
     *
     * @return mixed the column of the current row, false if no more rows available.
     */
    public function readColumn(int $columnIndex)
    {
        return $this->statement->fetchColumn($columnIndex);
    }

    /**
     * Returns an object populated with the next row of data.
     *
     * @param string $className class name of the object to be created and populated.
     * @param array $fields Elements of this array are passed to the constructor.
     *
     * @return mixed the populated object, false if no more row of data available.
     */
    public function readObject(string $className, array $fields)
    {
        return $this->statement->fetchObject($className, $fields);
    }

    /**
     * Reads the whole result set into an array.
     *
     * @return array the result set (each array element represents a row of data). An empty array will be returned if
     * the result contains no row.
     */
    public function readAll(): array
    {
        return $this->statement->fetchAll();
    }

    /**
     * Advances the reader to the next result when reading the results of a batch of statements. This method is only
     * useful when there are multiple result sets returned by the query. Not all DBMS support this feature.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function nextResult(): bool
    {
        if (($result = $this->statement->nextRowset()) !== false) {
            $this->index = -1;
        }

        return $result;
    }

    /**
     * Closes the reader.
     *
     * This frees up the resources allocated for executing this SQL statement. Read attempts after this method call are
     * unpredictable.
     */
    public function close(): void
    {
        $this->statement->closeCursor();
        $this->closed = true;
    }

    /**
     * whether the reader is closed or not.
     *
     * @return bool whether the reader is closed or not.
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Returns the number of rows in the result set.
     *
     * Note, most DBMS may not give a meaningful count. In this case, use "SELECT COUNT(*) FROM tableName" to obtain the
     * number of rows.
     *
     * @return int number of rows contained in the result.
     */
    public function getRowCount(): int
    {
        return $this->statement->rowCount();
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
        return $this->getRowCount();
    }

    /**
     * Returns the number of columns in the result set.
     *
     * Note, even there's no row in the reader, this still gives correct column number.
     *
     * @return int the number of columns in the result set.
     */
    public function getColumnCount(): int
    {
        return $this->statement->columnCount();
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
            $this->row = $this->statement->fetch();
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
    #[\ReturnTypeWillChange]
    public function current()
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
        $this->row = $this->statement->fetch();
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
