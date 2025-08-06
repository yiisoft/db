<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Between;

/**
 * @group db
 */
final class BetweenTest extends TestCase
{
    public function testConstructor(): void
    {
        $betweenCondition = new Between('date', 1, 2);

        $this->assertSame('date', $betweenCondition->column);
        $this->assertSame(1, $betweenCondition->intervalStart);
        $this->assertSame(2, $betweenCondition->intervalEnd);
    }

    public function testFromArrayDefinition(): void
    {
        $betweenCondition = Between::fromArrayDefinition('BETWEEN', ['date', 1, 2]);

        $this->assertSame('date', $betweenCondition->column);
        $this->assertSame(1, $betweenCondition->intervalStart);
        $this->assertSame(2, $betweenCondition->intervalEnd);
    }

    public function testFromArrayDefinitionExceptionWithoutOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires three operands.");

        Between::fromArrayDefinition('between', []);
    }

    public function testFromArrayDefinitionExceptionColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires column to be string or ExpressionInterface.");

        Between::fromArrayDefinition('between', [1, 'min_value', 'max_value']);
    }
}
