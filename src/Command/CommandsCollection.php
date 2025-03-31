<?php
namespace Yiisoft\Db\Command;

use Iterator;
use Countable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

class CommandsCollection implements Iterator, Countable
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
     * @param ConnectionInterface $connection Connection to a database.
     */
    public function __construct(private readonly ConnectionInterface $connection)
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
    public function insertBatch(string $table, array $rows, array $columns = []): static
    {
        $table = $this->connection->getQuoter()->getRawTableName($table);

        $columnsCount = count($columns);
        if ($columnsCount === 0 && count($rows) > 0) {
            $columnsCount = count(array_keys($rows[array_key_first($rows)]));
        }

        $maxParamsQty = $this->connection->getParamsLimit();
        $totalInsertedParams = $columnsCount * count($rows);

        if (!empty($maxParamsQty) && $totalInsertedParams > $maxParamsQty) {
            $chunkSize = (int)floor($maxParamsQty / $columnsCount);
            $rowChunks = array_chunk($rows, $chunkSize);
            foreach ($rowChunks as $rowChunk) {
                $this->commands[] = $this->createInsertBatchCommand($table, $rowChunk, $columns);
            }
        } else {
            $this->commands[] = $this->createInsertBatchCommand($table, $rows, $columns);
        }

        return $this;
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    private function createInsertBatchCommand(string $table, array $rows, array $columns = []): CommandInterface
    {
        $command = $this->connection->createCommand();
        $params = [];
        $sql = $this->connection->getQueryBuilder()->insertBatch($table, $rows, $columns, $params);

        $command->setRawSql($sql);
        $command->bindValues($params);

        return $command;
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
