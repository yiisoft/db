<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Iterator;
use Countable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\QueryBuilder\DMLQueryBuilderInterface;

/**
 * Object used as batch commands container
 *
 * @implements Iterator<int, mixed>
 *
 * @psalm-import-type BatchValues from DMLQueryBuilderInterface
 */
final class BatchCommand implements Iterator, Countable
{
    /**
     * @var int Current iterator position.
     */
    private int $position = 0;
    /**
     * @var CommandInterface[] Commands of the collection.
     */
    private array $commands = [];

    /**
     * @param ConnectionInterface $db Connection to a database.
     */
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): CommandInterface
    {
        return $this->commands[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->commands[$this->position]);
    }

    public function count(): int
    {
        return count($this->commands);
    }

    /**
     * Adds batch insert commands into execution queue
     *
     * @param string $table The name of the table to insert new rows into.
     * @param iterable $rows The rows to be batch inserted into the table.
     * @param string[] $columns The column names.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @psalm-param BatchValues $rows
     *
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function addInsertBatchCommand(string $table, iterable $rows, array $columns = []): void
    {
        $command = $this->db->createCommand();
        $params = [];
        $sql = $this->db->getQueryBuilder()->insertBatch($table, $rows, $columns, $params);

        $command->setRawSql($sql);
        $command->bindValues($params);

        $this->commands[] = $command;
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function execute(): int
    {
        $total = 0;
        foreach ($this->commands as $command) {
            $total += $command->execute();
        }

        return $total;
    }
}
