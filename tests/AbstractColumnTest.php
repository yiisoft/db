<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\Provider\ColumnProvider;

use function gettype;
use function is_object;

abstract class AbstractColumnTest extends TestCase
{
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
