<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stubs;

use PDO;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDO;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
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
    private Mock|null $mock = null;

    public function __construct(string $dsn)
    {
        parent::__construct(new PDODriver($dsn), $this->getMock()->getQueryCache(), $this->getMock()->getSchemaCache());
    }

    public function createCommand(string $sql = null, array $params = []): CommandInterface
    {
        $command = new Command($this, $this->getMock()->getQueryCache());

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

    public function getMock(): Mock
    {
        if ($this->mock === null) {
            $this->mock = new Mock();
        }

        return $this->mock;
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
            $this->schema = new Schema($this, $this->getMock()->getSchemaCache());
        }

        return $this->schema;
    }

    protected function initConnection(): void
    {
        if ($this->getEmulatePrepare() !== null) {
            $this->driver->attributes([PDO::ATTR_EMULATE_PREPARES => $this->getEmulatePrepare()]);
        }

        $this->pdo = $this->driver->createConnection();
    }
}
