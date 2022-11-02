<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stubs;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

final class Schema extends \Yiisoft\Db\Schema\Schema implements SchemaInterface
{
    public function __construct(ConnectionInterface $connection, SchemaCache $schemaCache)
    {
        parent::__construct($connection, $schemaCache);
    }

    public function createColumnSchemaBuilder(string $type, array|int|string $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        return [];
    }

    public function getLastInsertID(string $sequenceName = null): string
    {
        return '';
    }

    protected function getCacheKey(string $name): array
    {
        return [];
    }

    protected function getCacheTag(): string
    {
        return '';
    }

    protected function loadTableChecks(string $tableName): array
    {
        return [];
    }

    protected function loadTableDefaultValues(string $tableName): array
    {
        return [];
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        return [];
    }

    protected function loadTableIndexes(string $tableName): array
    {
        return [];
    }

    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        return [];
    }

    protected function loadTableUniques(string $tableName): array
    {
        return [];
    }

    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        return [];
    }

    public function supportsSavepoint(): bool
    {
        return false;
    }
}
