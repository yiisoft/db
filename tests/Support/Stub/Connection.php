<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use PDO;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Driver\Pdo\AbstractPdoConnection;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

final class Connection extends AbstractPdoConnection
{
    public function createCommand(?string $sql = null, array $params = []): CommandInterface
    {
        $command = new Command($this);

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
        return new Transaction($this);
    }

    public function getColumnFactory(): ColumnFactoryInterface
    {
        return new ColumnFactory();
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder ??= new QueryBuilder($this);
    }

    public function getQuoter(): QuoterInterface
    {
        return $this->quoter ??= new Quoter(['[', ']'], ['[', ']'], $this->getTablePrefix());
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema ??= new Schema($this, $this->schemaCache);
    }

    protected function initConnection(): void
    {
        if ($this->getEmulatePrepare() !== null) {
            $this->driver->attributes([PDO::ATTR_EMULATE_PREPARES => $this->getEmulatePrepare()]);
        }

        $this->pdo = $this->driver->createConnection();
    }
}
