<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function resetSequence(string $table, int|string|null $value = null): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params
    ): string {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }
}
