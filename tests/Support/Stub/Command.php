<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

final class Command extends AbstractPdoCommand
{
    public function showDatabases(): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    protected function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    protected function internalExecute(): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }
}
