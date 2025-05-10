<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function is_object;

abstract class AbstractColumnTest extends TestCase
{
    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::predefinedTypes */
    public function testPredefinedType(string $className, string $type, string $phpType)
    {
        $column = new $className();

        $this->assertSame($type, $column->getType());
        $this->assertSame($phpType, $column->getPhpType());
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::dbTypecastColumns */
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

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::phpTypecastColumns */
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
