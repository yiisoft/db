<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Simple;

/**
 * @group db
 */
final class SimpleTest extends TestCase
{
    public function testConstructor(): void
    {
        $simpleCondition = new Simple('id', '=', 1);

        $this->assertSame('id', $simpleCondition->column);
        $this->assertSame('=', $simpleCondition->operator);
        $this->assertSame(1, $simpleCondition->value);
    }

    public function testFromArrayDefinition(): void
    {
        $simpleCondition = Simple::fromArrayDefinition('=', ['id', 1]);

        $this->assertSame('id', $simpleCondition->column);
        $this->assertSame('=', $simpleCondition->operator);
        $this->assertSame(1, $simpleCondition->value);
    }

    public function testFromArrayDefinitionColumnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator '=' requires two operands.");

        Simple::fromArrayDefinition('=', []);
    }

    public function testFromArrayDefinitionValueException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'IN' requires two operands.");

        Simple::fromArrayDefinition('IN', ['column']);
    }

    public function testFromArrayDefinitionExceptionColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator '=' requires column to be string or ExpressionInterface."
        );

        Simple::fromArrayDefinition('=', [1, 1]);
    }

    public function testNullSecondOperand(): void
    {
        $condition = Simple::fromArrayDefinition('=', ['id', null]);

        $this->assertNull($condition->value);

        $condition2 = new Simple('name', 'IS NOT', null);

        $this->assertSame('IS NOT', $condition2->operator);
        $this->assertNull($condition2->value);
    }
}
