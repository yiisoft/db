<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Schema\Column\AbstractArrayColumn;
use Yiisoft\Db\Schema\Column\ArrayLazyColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonLazyColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredLazyColumn;
use Yiisoft\Db\Tests\Provider\ColumnFactoryProvider;

abstract class AbstractColumnFactoryTest extends TestCase
{
    abstract protected function getColumnFactoryClass(): string;

    abstract protected function getConnection(bool $fixture = false): PdoConnectionInterface;

    public function testConstructColumnClassMap(): void
    {
        $classMap = [
            ColumnType::ARRAY => ArrayLazyColumn::class,
            ColumnType::JSON => JsonLazyColumn::class,
            ColumnType::STRUCTURED => StructuredLazyColumn::class,
        ];

        $columnFactoryClass = $this->getColumnFactoryClass();
        $columnFactory = new $columnFactoryClass(classMap: $classMap);

        $this->assertInstanceOf(ArrayLazyColumn::class, $columnFactory->fromType(ColumnType::ARRAY));
        $this->assertInstanceOf(JsonLazyColumn::class, $columnFactory->fromType(ColumnType::JSON));
        $this->assertInstanceOf(StructuredLazyColumn::class, $columnFactory->fromType(ColumnType::STRUCTURED));
    }

    public function testConstructTypeMap(): void
    {
        $typeMap = [
            'json' => function (string $dbType, array &$info): string|null {
                if (str_ends_with($info['name'], '_ids')) {
                    $info['column'] = new IntegerColumn();
                    return ColumnType::ARRAY;
                }

                return null;
            },
        ];

        $columnFactoryClass = $this->getColumnFactoryClass();
        $columnFactory = new $columnFactoryClass(typeMap:  $typeMap);

        $column = $columnFactory->fromDbType('json', ['name' => 'user_ids']);

        $this->assertSame(ColumnType::ARRAY, $column->getType());
        $this->assertInstanceOf(AbstractArrayColumn::class, $column);
        $this->assertInstanceOf(IntegerColumn::class, $column->getColumn());
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

        $column = $columnFactory->fromDbType($dbType);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
        $this->assertSame($dbType, $column->getDbType());

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'definitions')]
    public function testFromDefinition(string $definition, ColumnInterface $expected): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

        $column = $columnFactory->fromDefinition($definition);

        $this->assertEquals($expected, $column);

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'pseudoTypes')]
    public function testFromPseudoType(string $pseudoType, ColumnInterface $expected): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

        $column = $columnFactory->fromPseudoType($pseudoType);

        $this->assertEquals($expected, $column);

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

        $column = $columnFactory->fromType($type);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());

        $db->close();
    }

    public function testFromDefinitionWithExtra(): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

        $column = $columnFactory->fromDefinition('char(1) INVISIBLE', ['extra' => 'COLLATE utf8mb4']);

        $this->assertInstanceOf(StringColumn::class, $column);
        $this->assertSame('char', $column->getType());
        $this->assertSame(1, $column->getSize());
        $this->assertSame('INVISIBLE COLLATE utf8mb4', $column->getExtra());

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'defaultValueRaw')]
    public function testFromTypeDefaultValueRaw(string $type, string|null $defaultValueRaw, mixed $expected): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

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
        $columnFactory = $db->getColumnFactory();

        $column = $columnFactory->fromType(ColumnType::INTEGER, ['defaultValueRaw' => '1', 'primaryKey' => true]);

        $this->assertNull($column->getDefaultValue());

        $column = $columnFactory->fromType(ColumnType::INTEGER, ['defaultValueRaw' => '1', 'computed' => true]);

        $this->assertNull($column->getDefaultValue());

        $db->close();
    }
}
