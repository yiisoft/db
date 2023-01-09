<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;
use Yiisoft\Db\Schema\AbstractSchema;
use Yiisoft\Db\Schema\TableSchemaInterface;

class Schema extends AbstractSchema
{
    public function createColumnSchemaBuilder(
        string $type,
        array|int|string $length = null
    ): AbstractColumnSchemaBuilder {
        return new ColumnSchemaBuilder($type, $length);
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
