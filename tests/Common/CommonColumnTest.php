<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Tests\AbstractColumnTest;
use Yiisoft\Db\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function version_compare;

abstract class CommonColumnTest extends AbstractColumnTest
{
    use TestTrait;

    protected const DATETIME_COLUMN_TABLE = 'datetime_column_test';

    abstract protected function insertTypeValues(ConnectionInterface $db): void;

    abstract protected function assertTypecastedValues(array $result, bool $allTypecasted = false): void;

    public function testQueryWithTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $query = $db->createQuery()->from('type')->withTypecasting();

        $result = $query->one();

        $this->assertTypecastedValues($result);

        $result = $query->all();

        $this->assertTypecastedValues($result[0]);

        $result = iterator_to_array($query->each());

        $this->assertTypecastedValues($result[0]);

        $result = iterator_to_array($query->batch());

        $this->assertTypecastedValues($result[0][0]);

        $result = $db->select(['float_col'])->from('type')->withTypecasting()->column();

        $this->assertSame(1.234, $result[0]);

        $db->close();
    }

    public function testCommandWithPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $quotedTableName = $db->getQuoter()->quoteSimpleTableName('type');
        $command = $db->createCommand("SELECT * FROM $quotedTableName")->withPhpTypecasting();

        $result = $command->queryOne();

        $this->assertTypecastedValues($result);

        $result = $command->queryAll();

        $this->assertTypecastedValues($result[0]);

        $result = iterator_to_array($command->query());

        $this->assertTypecastedValues($result[0]);

        $quotedColumnName = $db->getQuoter()->quoteSimpleColumnName('float_col');
        $result = $db->createCommand("SELECT $quotedColumnName FROM $quotedTableName")
            ->withPhpTypecasting()
            ->queryColumn();

        $this->assertSame(1.234, $result[0]);

        $db->close();
    }

    public function testPhpTypecast(): void
    {
        $db = $this->getConnection(true);
        $columns = $db->getTableSchema('type')->getColumns();

        $this->insertTypeValues($db);

        $query = $db->createQuery()->from('type')->one();

        $result = [];

        foreach ($columns as $columnName => $column) {
            $result[$columnName] = $column->phpTypecast($query[$columnName]);
        }

        $this->assertTypecastedValues($result, true);

        $db->close();
    }

    public function createDateTimeColumnTable(ConnectionInterface $db): void
    {
        $schema = $db->getSchema();
        $command = $db->createCommand();
        $columnBuilder = $db->getColumnBuilderClass();

        if ($schema->hasTable(static::DATETIME_COLUMN_TABLE)) {
            $command->dropTable(static::DATETIME_COLUMN_TABLE)->execute();
        }

        $command->createTable(static::DATETIME_COLUMN_TABLE, [
            'timestamp' => $columnBuilder::timestamp()->defaultValue(new Expression('CURRENT_TIMESTAMP')),
            'datetime' => $columnBuilder::datetime()->defaultValue('2025-04-19 14:11:35'),
            'datetime3' => $columnBuilder::datetime(3)->defaultValue(new Stringable('2025-04-19 14:11:35.123')),
            'datetimetz' => $columnBuilder::datetimeWithTimezone()->defaultValue(new DateTime('2025-04-19 14:11:35 +02:00')),
            'datetimetz6' => $columnBuilder::datetimeWithTimezone(6)->defaultValue(new DateTimeImmutable('2025-04-19 14:11:35.123456 +02:00')),
            'time' => $columnBuilder::time()->defaultValue('14:11:35'),
            'time3' => $columnBuilder::time(3)->defaultValue(new Stringable('14:11:35.123')),
            'timetz' => $columnBuilder::timeWithTimezone()->defaultValue(new DateTime('14:11:35 +02:00')),
            'timetz6' => $columnBuilder::timeWithTimezone(6)->defaultValue(new DateTimeImmutable('14:11:35.123456 +02:00')),
            'date' => $columnBuilder::date()->defaultValue('2025-04-19'),
        ])->execute();
    }

    public function testDateTimeColumnDefaults(): void
    {
        $db = $this->getConnection();

        $this->createDateTimeColumnTable($db);

        $columns = $db->getTableSchema(static::DATETIME_COLUMN_TABLE)->getColumns();

        $utcTimezone = new DateTimeZone('UTC');

        $this->assertEquals(
            new Expression(match ($db->getDriverName()) {
                'sqlsrv' => 'getdate()',
                'pgsql' => version_compare($db->getServerInfo()->getVersion(), '10', '<')
                    ? 'now()'
                    : 'CURRENT_TIMESTAMP',
                default => 'CURRENT_TIMESTAMP',
            }),
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
