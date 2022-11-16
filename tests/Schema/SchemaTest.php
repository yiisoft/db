<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Schema;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\AbstractSchemaTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends AbstractSchemaTest
{
    use TestTrait;

    public function testFindViewNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame([], Assert::invokeMethod($schema, 'findViewNames', ['dbo']));
    }

    public function testGetSchemaChecks(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema does not support fetching all table names.'
        );

        $schema->getSchemaChecks();
    }

    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema does not support fetching all table names.'
        );

        $schema->getSchemaDefaultValues();
    }

    public function testGetSchemaForeignKeys(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema does not support fetching all table names.'
        );

        $schema->getSchemaForeignKeys();
    }

    public function testGetSchemaIndexes(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema does not support fetching all table names.'
        );

        $schema->getSchemaIndexes();
    }

    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema does not support fetching all schema names.'
        );

        $schema->getSchemaNames();
    }

    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema does not support fetching all table names.'
        );

        $schema->getSchemaPrimaryKeys();
    }

    public function testGetSchemaUniques(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema does not support fetching all table names.'
        );

        $schema->getSchemaUniques();
    }

    public function testGetTableChecks(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableChecks() is not supported by core-db.'
        );

        $schema->getTableChecks('customer');
    }

    public function testGetTableDefaultValues(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableDefaultValues() is not supported by core-db.'
        );

        $schema->getTableDefaultValues('customer');
    }

    public function testGetTableForeignKeys(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableForeignKeys() is not supported by core-db.'
        );

        $schema->getTableForeignKeys('customer');
    }

    public function testResolveTableName(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Tests\Support\Stubs\Schema does not support resolving table names.');

        Assert::invokeMethod($schema, 'resolveTableName', ['customer']);
    }
}
