<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;

use function is_object;

abstract class CommonColumnTest extends TestCase
{
    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::predefinedTypes */
    public function testPredefinedType(string $className, string $type, string $phpType)
    {
        $column = new $className();

        $this->assertSame($type, $column->getType());
        $this->assertSame($phpType, $column->getPhpType());
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::dbTypecastColumns */
    public function testDbTypecastColumns(string $className, array $values)
    {
        $column = new $className();

        foreach ($values as [$expected, $value]) {
            if (is_object($expected) && !(is_object($value) && $expected::class === $value::class)) {
                $this->assertEquals($expected, $column->dbTypecast($value));
            } else {
                $this->assertSame($expected, $column->dbTypecast($value));
            }
        }
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::phpTypecastColumns */
    public function testPhpTypecastColumns(string $className, array $values)
    {
        $column = new $className();

        foreach ($values as [$expected, $value]) {
            $this->assertSame($expected, $column->phpTypecast($value));
        }
    }
}
