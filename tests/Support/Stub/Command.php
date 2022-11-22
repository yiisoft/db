<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Driver\PDO\CommandPDO;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

final class Command extends CommandPDO implements CommandPDOInterface
{
    public function insertEx(string $table, array $columns): bool|array
    {
        throw new NotSupportedException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    protected function internalExecute(string|null $rawSql): void
    {
        throw new NotSupportedException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }
}
