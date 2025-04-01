<?php
namespace Yiisoft\Db\Command;

use Iterator;
use Countable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

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

    function rewind(): void {
        $this->position = 0;
    }

    function current(): CommandInterface {
        return $this->commands[$this->position];
    }

    function key(): int {
        return $this->position;
    }

    function next(): void {
        ++$this->position;
    }

    function valid(): bool {
        return isset($this->commands[$this->position]);
    }

    function count(): int
    {
        return count($this->commands);
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function addInsertBatchCommand(string $table, array $rows, array $columns = []): void
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
