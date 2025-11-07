<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\AbstractSchema;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

class Schema extends AbstractSchema
{
    protected function getCacheKey(string $name): array
    {
        return [];
    }

    protected function getCacheTag(): string
    {
        return '';
    }

    protected function getResultColumnCacheKey(array $metadata): string
    {
        return md5(serialize([self::class, ...$metadata]));
    }

    protected function loadResultColumn(array $metadata): ?ColumnInterface
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableChecks(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableIndexes(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    protected function loadTableSchema(string $name): ?TableSchemaInterface
    {
        return null;
    }
}
