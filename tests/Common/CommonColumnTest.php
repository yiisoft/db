<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\AbstractColumnTest;
use Yiisoft\Db\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_fill_keys;
use function array_keys;
use function array_map;

abstract class CommonColumnTest extends AbstractColumnTest
{
    use TestTrait;

    protected const DATETIME_COLUMN_TABLE = 'datetime_column_test';

    protected const COLUMN_BUILDER = ColumnBuilder::class;

    public function createDateTimeColumnTable(ConnectionInterface $db): void
    {
        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->hasTable(static::DATETIME_COLUMN_TABLE)) {
            $command->dropTable(static::DATETIME_COLUMN_TABLE)->execute();
        }

        $command->createTable(static::DATETIME_COLUMN_TABLE, [
            'timestamp' => static::COLUMN_BUILDER::timestamp()->defaultValue(new Expression('CURRENT_TIMESTAMP')),
            'datetime' => static::COLUMN_BUILDER::datetime()->defaultValue('2025-04-19 14:11:35'),
            'datetime3' => static::COLUMN_BUILDER::datetime(3)->defaultValue(new Stringable('2025-04-19 14:11:35.123')),
            'datetimetz' => static::COLUMN_BUILDER::datetimeWithTimezone()->defaultValue(new DateTime('2025-04-19 14:11:35 +02:00')),
            'datetimetz6' => static::COLUMN_BUILDER::datetimeWithTimezone(6)->defaultValue(new DateTimeImmutable('2025-04-19 14:11:35.123456 +02:00')),
            'time' => static::COLUMN_BUILDER::time()->defaultValue('14:11:35'),
            'time3' => static::COLUMN_BUILDER::time(3)->defaultValue(new Stringable('14:11:35.123')),
            'timetz' => static::COLUMN_BUILDER::timeWithTimezone()->defaultValue(new DateTime('14:11:35 +02:00')),
            'timetz6' => static::COLUMN_BUILDER::timeWithTimezone(6)->defaultValue(new DateTimeImmutable('14:11:35.123456 +02:00')),
            'date' => static::COLUMN_BUILDER::date()->defaultValue('2025-04-19'),
        ])->execute();
    }

    public function testDateTimeColumnDefaults(): void
    {
        $db = $this->getConnection();

        $this->createDateTimeColumnTable($db);

        $columns = $db->getTableSchema(static::DATETIME_COLUMN_TABLE)->getColumns();

        $utcTimezone = new DateTimeZone('UTC');

        $this->assertEquals(
            new Expression($db->getDriverName() === 'sqlsrv' ? 'getdate()' : 'CURRENT_TIMESTAMP'),
            $columns['timestamp']->getDefaultValue(),
        );
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35', $utcTimezone), $columns['datetime']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35.123', $utcTimezone), $columns['datetime3']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35 +02:00', $utcTimezone), $columns['datetimetz']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35.123456 +02:00', $utcTimezone), $columns['datetimetz6']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('14:11:35', $utcTimezone), $columns['time']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('14:11:35.123', $utcTimezone), $columns['time3']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('14:11:35 +02:00', $utcTimezone), $columns['timetz']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('14:11:35.123456 +02:00', $utcTimezone), $columns['timetz6']->getDefaultValue());
        $this->assertEquals(new DateTimeImmutable('2025-04-19', $utcTimezone), $columns['date']->getDefaultValue());

        $db->close();
    }

    #[DataProviderExternal(ColumnProvider::class, 'dateTimeColumn')]
    public function testDateTimeColumn(float|int|string $value, array $expected): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $this->createDateTimeColumnTable($db);

        $values = array_fill_keys(array_keys($expected), $value);

        $expected = array_map(static fn (string $value) => new DateTimeImmutable($value, new DateTimeZone('UTC')), $expected);

        $command->insert(static::DATETIME_COLUMN_TABLE, $values)->execute();

        $result = $command
            ->setSql('SELECT * FROM [[' . static::DATETIME_COLUMN_TABLE . ']]')
            ->withPhpTypecasting()
            ->queryOne();

        $this->assertEquals($expected, $result);

        $db->close();
    }
}
