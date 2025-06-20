<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\Provider\ColumnProvider;

use function gettype;
use function is_object;

abstract class AbstractColumnTest extends TestCase
{
    abstract protected function getConnection(bool $fixture = false): PdoConnectionInterface;

    abstract protected function insertTypeValues(PdoConnectionInterface $db): void;

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

    #[DataProviderExternal(ColumnProvider::class, 'predefinedTypes')]
    public function testPredefinedType(string $className, string $type, string $phpType)
    {
        $column = new $className();

        $this->assertSame($type, $column->getType());
        $this->assertSame($phpType, $column->getPhpType());
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumns')]
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        // Set the timezone for testing purposes, could be any timezone except UTC
        $oldDatetime = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        foreach ($values as [$expected, $value]) {
            if (is_object($expected) && !(is_object($value) && $expected::class === $value::class)) {
                $this->assertEquals($expected, $column->dbTypecast($value));
            } else {
                $this->assertSame($expected, $column->dbTypecast($value));
            }
        }

        date_default_timezone_set($oldDatetime);
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumnsWithException')]
    public function testDbTypecastColumnsWithException(ColumnInterface $column, mixed $value)
    {
        $type = is_object($value) ? $value::class : gettype($value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Wrong $type value for {$column->getType()} column.");

        $column->dbTypecast($value);
    }

    #[DataProviderExternal(ColumnProvider::class, 'phpTypecastColumns')]
    public function testPhpTypecastColumns(ColumnInterface $column, array $values)
    {
        foreach ($values as [$expected, $value]) {
            if (is_object($expected) && !(is_object($value) && $expected::class === $value::class)) {
                $this->assertEquals($expected, $column->phpTypecast($value));
            } else {
                $this->assertSame($expected, $column->phpTypecast($value));
            }
        }
    }
}
