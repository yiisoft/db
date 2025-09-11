<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ArrayLazyColumn;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Tests\AbstractColumnFactoryTest;
use Yiisoft\Db\Tests\Support\Stub\ColumnFactory;
use Yiisoft\Db\Tests\Support\TestTrait;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;

/**
 * @group db
 */
final class ColumnFactoryTest extends AbstractColumnFactoryTest
{
    use TestTrait;

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

        $columnFactoryClass = $this->getColumnFactoryClass();
        $columnFactory = new $columnFactoryClass(definitions: $definitions);

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

    protected function getColumnFactoryClass(): string
    {
        return ColumnFactory::class;
    }
}
