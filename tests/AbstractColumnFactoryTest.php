<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Tests\Provider\ColumnBuilderProvider;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractColumnFactoryTest extends TestCase
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider::types */
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromDbType($dbType);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
        $this->assertSame($dbType, $column->getDbType());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider::definitions
     */
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
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider::pseudoTypes */
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
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider::types */
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        $column = $columnFactory->fromType($type);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
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
    }
}
