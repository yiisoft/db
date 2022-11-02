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

    public function testFromArrayDefinitionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator '=' requires two operands.");
        SimpleCondition::fromArrayDefinition('=', []);
    }

    public function testFromArrayDefinitionExceptionColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator '=' requires column to be string, ExpressionInterface or QueryInterface."
        );
        SimpleCondition::fromArrayDefinition('=', [1, 1]);
    }
}
