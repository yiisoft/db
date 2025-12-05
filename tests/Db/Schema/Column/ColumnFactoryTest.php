<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractArrayColumn;
use Yiisoft\Db\Schema\Column\ArrayLazyColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonLazyColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredLazyColumn;
use Yiisoft\Db\Tests\Provider\ColumnFactoryProvider;
use Yiisoft\Db\Tests\Support\Stub\StubColumnFactory;

use function is_scalar;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;

/**
 * @group db
 */
final class ColumnFactoryTest extends TestCase
{
    public function testConstructColumnClassMap(): void
    {
        $columnFactory = new StubColumnFactory(
            definitions: [
                ColumnType::ARRAY => ArrayLazyColumn::class,
                ColumnType::JSON => JsonLazyColumn::class,
                ColumnType::STRUCTURED => StructuredLazyColumn::class,
            ],
        );

        $this->assertInstanceOf(ArrayLazyColumn::class, $columnFactory->fromType(ColumnType::ARRAY));
        $this->assertInstanceOf(JsonLazyColumn::class, $columnFactory->fromType(ColumnType::JSON));
        $this->assertInstanceOf(StructuredLazyColumn::class, $columnFactory->fromType(ColumnType::STRUCTURED));
    }

    public function testConstructTypeMap(): void
    {
        $columnFactory = new StubColumnFactory(
            map: [
                'json' => function (string $dbType, array &$info): ?string {
                    if (str_ends_with($info['name'], '_ids')) {
                        $info['column'] = new IntegerColumn();
                        return ColumnType::ARRAY;
                    }

                    return null;
                },
            ],
        );

        $column = $columnFactory->fromDbType('json', ['name' => 'user_ids']);

        $this->assertSame(ColumnType::ARRAY, $column->getType());
        $this->assertInstanceOf(AbstractArrayColumn::class, $column);
        $this->assertInstanceOf(IntegerColumn::class, $column->getColumn());
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        $columnFactory = new StubColumnFactory();

        $column = $columnFactory->fromDbType($dbType);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
        $this->assertSame($dbType, $column->getDbType());
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'definitions')]
    public function testFromDefinition(string $definition, ColumnInterface $expected): void
    {
        $columnFactory = new StubColumnFactory();

        $column = $columnFactory->fromDefinition($definition);

        $this->assertEquals($expected, $column);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'pseudoTypes')]
    public function testFromPseudoType(string $pseudoType, ColumnInterface $expected): void
    {
        $columnFactory = new StubColumnFactory();

        $column = $columnFactory->fromPseudoType($pseudoType);

        $this->assertEquals($expected, $column);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        $columnFactory = new StubColumnFactory();

        $column = $columnFactory->fromType($type);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());
    }

    public function testFromDefinitionWithExtra(): void
    {
        $columnFactory = new StubColumnFactory();

        $column = $columnFactory->fromDefinition('char(1) INVISIBLE', ['extra' => 'COLLATE utf8mb4']);

        $this->assertInstanceOf(StringColumn::class, $column);
        $this->assertSame('char', $column->getType());
        $this->assertSame(1, $column->getSize());
        $this->assertSame('INVISIBLE COLLATE utf8mb4', $column->getExtra());
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'defaultValueRaw')]
    public function testFromTypeDefaultValueRaw(string $type, ?string $defaultValueRaw, mixed $expected): void
    {
        $columnFactory = new StubColumnFactory();

        $column = $columnFactory->fromType($type, ['defaultValueRaw' => $defaultValueRaw]);

        if (is_scalar($expected)) {
            $this->assertSame($expected, $column->getDefaultValue());
        } else {
            $this->assertEquals($expected, $column->getDefaultValue());
        }
    }

    public function testNullDefaultValueRaw(): void
    {
        $columnFactory = new StubColumnFactory();

        $column = $columnFactory->fromType(ColumnType::INTEGER, ['defaultValueRaw' => '1', 'primaryKey' => true]);

        $this->assertNull($column->getDefaultValue());

        $column = $columnFactory->fromType(ColumnType::INTEGER, ['defaultValueRaw' => '1', 'computed' => true]);

        $this->assertNull($column->getDefaultValue());
    }

    public function testDefinitions(): void
    {
        $definitions = [
            ColumnType::ARRAY => ArrayLazyColumn::class,
            ColumnType::DATETIME => [
                DateTimeColumn::class,
                'dbTimezone' => '+02:00',
                'phpTimezone' => '+04:00',
            ],
            ColumnType::DATETIMETZ => [
                'dbTimezone' => '+02:00',
                'phpTimezone' => '+04:00',
            ],
        ];

        $columnFactory = new StubColumnFactory(definitions: $definitions);

        $this->assertInstanceOf(ArrayLazyColumn::class, $columnFactory->fromType(ColumnType::ARRAY));

        /** @var DateTimeColumn $column */
        $column = $columnFactory->fromType(ColumnType::DATETIME);
        assertInstanceOf(DateTimeColumn::class, $column);
        assertSame('2025-07-31 13:45:00.000000', $column->dbTypecast('2025-07-31T14:45:00+03:00'));
        assertSame('2025-07-31 12:45:00.000000', $column->dbTypecast('2025-07-31T14:45:00'));

        /** @var DateTimeColumn $column */
        $column = $columnFactory->fromType(ColumnType::DATETIMETZ);
        assertInstanceOf(DateTimeColumn::class, $column);
        assertSame('2025-07-31 14:45:00.000000+03:00', $column->dbTypecast('2025-07-31T14:45:00+03:00'));
        assertSame('2025-07-31 14:45:00.000000+04:00', $column->dbTypecast('2025-07-31T14:45:00'));
    }
}
