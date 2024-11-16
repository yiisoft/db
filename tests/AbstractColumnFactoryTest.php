<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Tests\Provider\ColumnBuilderProvider;
use Yiisoft\Db\Tests\Provider\ColumnFactoryProvider;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractColumnFactoryTest extends TestCase
{
    use TestTrait;

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromDbType($dbType);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
        $this->assertSame($dbType, $column->getDbType());

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'definitions')]
    public function testFromDefinition(
        string $definition,
        string $expectedType,
        string $expectedInstanceOf,
        array $expectedMethodResults = []
    ): void {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromDefinition($definition);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());

        $columnMethodResults = array_merge(
            ColumnBuilderProvider::DEFAULT_COLUMN_METHOD_RESULTS,
            $expectedMethodResults,
        );

        foreach ($columnMethodResults as $method => $result) {
            $this->assertSame($result, $column->$method());
        }

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'pseudoTypes')]
    public function testFromPseudoType(
        string $pseudoType,
        string $expectedType,
        string $expectedInstanceOf,
        array $expectedMethodResults = []
    ): void {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromPseudoType($pseudoType);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());

        $columnMethodResults = array_merge(
            ColumnBuilderProvider::DEFAULT_COLUMN_METHOD_RESULTS,
            $expectedMethodResults,
        );

        foreach ($columnMethodResults as $method => $result) {
            $this->assertEquals($result, $column->$method());
        }

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromType($type);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());

        $db->close();
    }

    public function testFromDefinitionWithExtra(): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromDefinition('char(1) NOT NULL', ['extra' => 'UNIQUE']);

        $this->assertInstanceOf(StringColumnSchema::class, $column);
        $this->assertSame('char', $column->getType());
        $this->assertSame(1, $column->getSize());
        $this->assertSame('NOT NULL UNIQUE', $column->getExtra());

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'defaultValueRaw')]
    public function testFromTypeDefaultValueRaw(string $type, string|null $defaultValueRaw, mixed $expected): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromType($type, ['defaultValueRaw' => $defaultValueRaw]);

        if (is_scalar($expected)) {
            $this->assertSame($expected, $column->getDefaultValue());
        } else {
            $this->assertEquals($expected, $column->getDefaultValue());
        }

        $db->close();
    }

    public function testNullDefaultValueRaw(): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromType(ColumnType::INTEGER, ['defaultValueRaw' => '1', 'primaryKey' => true]);

        $this->assertNull($column->getDefaultValue());

        $column = $columnFactory->fromType(ColumnType::INTEGER, ['defaultValueRaw' => '1', 'computed' => true]);

        $this->assertNull($column->getDefaultValue());

        $db->close();
    }
}
