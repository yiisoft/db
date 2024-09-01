<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractColumnFactoryTest extends TestCase
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider::types */
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $factory = $db->getSchema()->getColumnFactory();

        $column = $factory->fromDbType($dbType);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider::definitions
     */
    public function testFromDefinition(
        string $definition,
        string $expectedType,
        string $expectedInstanceOf,
        array $expectedInfo = []
    ): void {
        $db = $this->getConnection();
        $factory = $db->getSchema()->getColumnFactory();

        $column = $factory->fromDefinition($definition);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());

        foreach ($expectedInfo as $method => $value) {
            $this->assertSame($value, $column->$method());
        }
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider::types */
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $factory = $db->getSchema()->getColumnFactory();

        $column = $factory->fromType($type);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
    }

    public function testFromDefinitionWithExtra(): void
    {
        $db = $this->getConnection();
        $factory = $db->getSchema()->getColumnFactory();

        $column = $factory->fromDefinition('char(1) NOT NULL', ['extra' => 'UNIQUE']);

        $this->assertInstanceOf(StringColumnSchema::class, $column);
        $this->assertSame('char', $column->getType());
        $this->assertSame(1, $column->getSize());
        $this->assertSame('NOT NULL UNIQUE', $column->getExtra());
    }
}
