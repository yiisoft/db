<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractTableSchemaTest extends TestCase
{
    use TestTrait;

    public function testConstructorEmpty(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame('', $tableSchema->getName());
        $this->assertSame('', $tableSchema->getFullName());
        $this->assertSame('', $tableSchema->getSchemaName());
        $this->assertSame([], $tableSchema->getChecks());
        $this->assertSame([], $tableSchema->getColumns());
        $this->assertSame([], $tableSchema->getColumnNames());
        $this->assertNull($tableSchema->getComment());
        $this->assertNull($tableSchema->getCreateSql());
        $this->assertSame([], $tableSchema->getDefaultValues());
        $this->assertSame([], $tableSchema->getForeignKeys());
        $this->assertSame([], $tableSchema->getPrimaryKey());
        $this->assertNull($tableSchema->getSequenceName());
        $this->assertSame([], $tableSchema->getUniques());
    }

    public function testConstructorWithTable(): void
    {
        $tableSchema = new TableSchema('test');

        $this->assertSame('test', $tableSchema->getName());
        $this->assertSame('test', $tableSchema->getFullName());
        $this->assertSame('', $tableSchema->getSchemaName());
    }

    public function testConstructorWithTableSchema(): void
    {
        $tableSchema = new TableSchema('test', 'yiisoft');

        $this->assertSame('test', $tableSchema->getName());
        $this->assertSame('yiisoft.test', $tableSchema->getFullName());
        $this->assertSame('yiisoft', $tableSchema->getSchemaName());
    }

    public function testGetComment(): void
    {
        $tableSchema = new TableSchema();

        $this->assertNull($tableSchema->getComment());

        $tableSchema->comment('test');

        $this->assertSame('test', $tableSchema->getComment());
    }

    public function testGetColumn(): void
    {
        $column = ColumnBuilder::primaryKey();
        $tableSchema = new TableSchema();

        $this->assertNull($tableSchema->getColumn('id'));

        $tableSchema->column('id', $column);

        $this->assertSame($column, $tableSchema->getColumn('id'));
    }

    public function testGetColumns(): void
    {
        $column = ColumnBuilder::primaryKey();
        $tableSchema = new TableSchema();

        $this->assertSame([], $tableSchema->getColumns());

        $tableSchema->column('id', $column);

        $this->assertSame(['id' => $column], $tableSchema->getColumns());
    }

    public function testGetColumnName(): void
    {
        $column = ColumnBuilder::primaryKey();
        $tableSchema = new TableSchema();

        $this->assertNull($tableSchema->getColumn('id'));

        $tableSchema->column('id', $column);

        $this->assertSame(['id'], $tableSchema->getColumnNames());
    }

    public function testGetCreateSql(): void
    {
        $tableSchema = new TableSchema();

        $this->assertNull($tableSchema->getCreateSql());

        $tableSchema->createSql(
            <<<SQL
            CREATE TABLE `test` (`id` int(11) NOT NULL)
            SQL,
        );

        $this->assertSame(
            <<<SQL
            CREATE TABLE `test` (`id` int(11) NOT NULL)
            SQL,
            $tableSchema->getCreateSql(),
        );
    }

    public function testChecks(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame([], $tableSchema->getChecks());

        $checks = ['check1' => new Check('check1')];
        $tableSchema->checks(...$checks);

        $this->assertSame($checks, $tableSchema->getChecks());
    }

    public function testDefaultValues(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame([], $tableSchema->getDefaultValues());

        $defaults = ['value1' => new DefaultValue('value1')];
        $tableSchema->defaultValues(...$defaults);

        $this->assertSame($defaults, $tableSchema->getDefaultValues());
    }

    public function testForeignKeys(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame([], $tableSchema->getForeignKeys());

        $foreignKeys = ['fk1' => new ForeignKey('fk1')];
        $tableSchema->foreignKeys(...$foreignKeys);

        $this->assertSame($foreignKeys, $tableSchema->getForeignKeys());
    }

    public function testIndexes(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame([], $tableSchema->getIndexes());

        $indexes = [
            'pk' => new Index('pk', ['id'], true, true),
            'index1' => new Index('index1'),
            'unique1' => new Index('unique1', ['unic_column'], true),
        ];
        $tableSchema->indexes(...$indexes);

        $this->assertSame($indexes, $tableSchema->getIndexes());
        $this->assertSame(['id'], $tableSchema->getPrimaryKey());
        $this->assertSame(['pk' => $indexes['pk'], 'unique1' => $indexes['unique1']], $tableSchema->getUniques());
    }

    public function testOptions(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame([], $tableSchema->getOptions());

        $options = ['ROW_FORMAT FIXED'];
        $tableSchema->options(...$options);

        $this->assertSame($options, $tableSchema->getOptions());
    }

    public function testGetName(): void
    {
        $tableSchema = new TableSchema();

        $this->assertEmpty($tableSchema->getName());

        $tableSchema->name('test');

        $this->assertSame('test', $tableSchema->getName());
    }

    public function testGetPrimaryKey(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame([], $tableSchema->getPrimaryKey());

        $tableSchema->column('id', ColumnBuilder::primaryKey());

        $this->assertSame(['id'], $tableSchema->getPrimaryKey());
    }

    public function testGetSequenceName(): void
    {
        $tableSchema = new TableSchema();

        $this->assertNull($tableSchema->getSequenceName());

        $tableSchema->sequenceName('test');

        $this->assertSame('test', $tableSchema->getSequenceName());
    }

    public function testGetSchemaName(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame('', $tableSchema->getSchemaName());

        $tableSchema->schemaName('test');

        $this->assertSame('test', $tableSchema->getSchemaName());
    }
}
