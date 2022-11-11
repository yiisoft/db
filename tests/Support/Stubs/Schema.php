<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stubs;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\NotSupportedException;
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
        return $this->getException(self::class . '::findUniqueIndexes()' . ' is not supported by core-db.');
    }

    public function getLastInsertID(string $sequenceName = null): string
    {
        $this->getException(self::class . '::getLastInsertID() is not supported by core-db.');
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
        $this->getException(self::class . '::loadTableChecks() is not supported by core-db.');
    }

    protected function loadTableDefaultValues(string $tableName): array
    {
        $this->getException(self::class . '::loadTableDefaultValues() is not supported by core-db.');
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        $this->getException(self::class . '::loadTableForeignKeys() is not supported by core-db.');
    }

    protected function loadTableIndexes(string $tableName): array
    {
        $this->getException();
    }

    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        $this->getException(self::class . '::loadTablePrimaryKey() is not supported by core-db.');
    }

    protected function loadTableUniques(string $tableName): array
    {
        $this->getException(self::class . '::loadTableUniques() is not supported by core-db.');
    }

    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        $this->getException(self::class . '::loadTableSchema() is not supported by core-db.');
    }

    private function getException(string $message): void
    {
        throw new NotSupportedException($message);
    }
}
