<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder\Conditions;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Conditions\SimpleCondition;

/**
 * @group db
 */
final class SimpleConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $simpleCondition = new SimpleCondition('id', '=', 1);

        $this->assertSame('id', $simpleCondition->getColumn());
        $this->assertSame('=', $simpleCondition->getOperator());
        $this->assertSame(1, $simpleCondition->getValue());
    }

    public function testFromArrayDefinition(): void
    {
        $simpleCondition = SimpleCondition::fromArrayDefinition('=', ['id', 1]);

        $this->assertSame('id', $simpleCondition->getColumn());
        $this->assertSame('=', $simpleCondition->getOperator());
        $this->assertSame(1, $simpleCondition->getValue());
    }

    public function testFromArrayDefinitionColumnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator '=' requires column.");
        SimpleCondition::fromArrayDefinition('=', []);
    }

    public function testFromArrayDefinitionValueException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'IN' requires value as second operand.");
        SimpleCondition::fromArrayDefinition('IN', ['column']);
    }

    public function testFromArrayDefinitionExceptionColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator '=' requires column to be string, ExpressionInterface or QueryInterface."
        );
        SimpleCondition::fromArrayDefinition('=', [1, 1]);
    }

    public function testNullSecondOperand(): void
    {
        $condition = SimpleCondition::fromArrayDefinition('=', ['id', null]);
        $this->assertNull($condition->getValue());

        $condition2 = new SimpleCondition('name', 'IS NOT', null);
        $this->assertSame('IS NOT', $condition2->getOperator());
        $this->assertNull($condition2->getValue());
    }
}
