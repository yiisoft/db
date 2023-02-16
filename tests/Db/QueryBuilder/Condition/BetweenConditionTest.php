<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\BetweenCondition;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BetweenConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $betweenCondition = new BetweenCondition('date', 'BETWEEN', 1, 2);

        $this->assertSame('date', $betweenCondition->getColumn());
        $this->assertSame('BETWEEN', $betweenCondition->getOperator());
        $this->assertSame(1, $betweenCondition->getIntervalStart());
        $this->assertSame(2, $betweenCondition->getIntervalEnd());
    }

    public function testFromArrayDefinition(): void
    {
        $betweenCondition = BetweenCondition::fromArrayDefinition('BETWEEN', ['date', 1, 2]);

        $this->assertSame('date', $betweenCondition->getColumn());
        $this->assertSame('BETWEEN', $betweenCondition->getOperator());
        $this->assertSame(1, $betweenCondition->getIntervalStart());
        $this->assertSame(2, $betweenCondition->getIntervalEnd());
    }

    public function testFromArrayDefinitionExceptionWithoutOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires three operands.");

        BetweenCondition::fromArrayDefinition('between', []);
    }

    public function testFromArrayDefinitionExceptionColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires column to be string or ExpressionInterface.");

        BetweenCondition::fromArrayDefinition('between', [1, 'min_value', 'max_value']);
    }
}
