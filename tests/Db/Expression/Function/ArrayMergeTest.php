<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Function;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Schema\Column\IntegerColumn;

final class ArrayMergeTest extends TestCase
{
    public function testType(): void
    {
        $expression = new ArrayMerge();

        $this->assertSame('', $expression->getType());
        $this->assertSame($expression, $expression->type('integer'));
        $this->assertSame('integer', $expression->getType());

        $intColumn = new IntegerColumn();
        $this->assertSame($expression, $expression->type($intColumn));
        $this->assertSame($intColumn, $expression->getType());
    }

    public function testOrdered(): void
    {
        $expression = new ArrayMerge();

        $this->assertFalse($expression->getOrdered());
        $this->assertSame($expression, $expression->ordered());
        $this->assertTrue($expression->getOrdered());
        $this->assertSame($expression, $expression->ordered(false));
        $this->assertFalse($expression->getOrdered());
    }
}
