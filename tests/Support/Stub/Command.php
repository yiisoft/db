<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\PDO\AbstractCommandPDO;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

final class Command extends AbstractCommandPDO
{
    public function insertWithReturningPks(string $table, array $columns): bool|array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    protected function internalExecute(string|null $rawSql): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }
}
