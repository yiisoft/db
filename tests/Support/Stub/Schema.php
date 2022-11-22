<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

final class Schema extends \Yiisoft\Db\Schema\Schema implements SchemaInterface
{
    public function createColumnSchemaBuilder(string $type, array|int|string $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        return $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    public function getLastInsertID(string $sequenceName = null): string
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function getCacheKey(string $name): array
    {
        throw new NotSupportedException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function getCacheTag(): string
    {
        throw new NotSupportedException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function loadTableChecks(string $tableName): array
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function loadTableDefaultValues(string $tableName): array
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function loadTableIndexes(string $tableName): array
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function loadTableUniques(string $tableName): array
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        $this->getException(self::class . '::' . __METHOD__ . '()' . ' is not supported by core-db.');
    }

    private function getException(string $message): void
    {
        throw new NotSupportedException($message);
    }
}
