<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stubs;

use Yiisoft\Db\Driver\PDO\CommandPDO;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDO;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Driver\PDO\PDODriver;
use Yiisoft\Db\Driver\PDO\PDODriverInterface;
use Yiisoft\Db\Driver\PDO\TransactionPDO;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\Mock;
use Yiisoft\Db\Transaction\TransactionInterface;

final class Connection extends ConnectionPDO implements ConnectionPDOInterface
{
    protected QueryBuilderInterface|null $queryBuilder = null;
    protected SchemaInterface|null $schema = null;

    public function __construct()
    {
        $this->mock = new Mock();

        parent::__construct($this->pdoDriver(), $this->mock->getQueryCache(), $this->mock->getSchemaCache());
    }

    protected function initConnection(): void
    {
    }

    public function createCommand(string $sql = null, array $params = []): CommandPDOInterface
    {
        $command = new CommandPDO($this, $this->mock->getQueryCache());

        if ($sql !== null) {
            $command->setSql($sql);
        }

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    public function createTransaction(): TransactionInterface
    {
        return new TransactionPDO($this);
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder(
                $this->getQuoter(),
                $this->getSchema(),
            );
        }

        return $this->queryBuilder;
    }

    public function getQuoter(): QuoterInterface
    {
        if ($this->quoter === null) {
            $this->quoter = new Quoter('`', '`', $this->getTablePrefix());
        }

        return $this->quoter;
    }

    public function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this, $this->mock->getSchemaCache());
        }

        return $this->schema;
    }

    private function pdoDriver(): PDODriverInterface
    {
        return new class ('sqlite::memory:') extends PDODriver implements PDODriverInterface {
            public function __construct(string $dsn)
            {
                parent::__construct($dsn);
            }

            public function getDriverName(): string
            {
                return 'sqlite';
            }
        };
    }
}
