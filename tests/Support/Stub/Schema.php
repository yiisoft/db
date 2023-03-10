<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\AbstractSchema;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

/**
 * @psalm-suppress InvalidReturnType
 * @psalm-suppress InvalidNullableReturnType
 * @psalm-suppress NullableReturnStatement
 */
class Schema extends AbstractSchema
{
    public function createColumn(string $type, array|int|string $length = null): ColumnInterface
    {
        return new Column($type, $length);
    }

    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     */
    public function getLastInsertID(string $sequenceName = null): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    protected function getCacheKey(string $name): array
    {
        return [];
    }

    protected function getCacheTag(): string
    {
        return '';
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

    /**
     * @throws NotSupportedException
     */
    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableUniques(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     */
    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }
}
