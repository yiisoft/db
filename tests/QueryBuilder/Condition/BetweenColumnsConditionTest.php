<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder\Conditions;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Conditions\BetweenColumnsCondition;

/**
 * @group db
 */
final class BetweenColumnsConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $condition = new BetweenColumnsCondition(42, 'between', 'min_value', 'max_value');
        $this->assertSame(42, $condition->getValue());
        $this->assertSame('between', $condition->getOperator());
        $this->assertSame('min_value', $condition->getIntervalStartColumn());
        $this->assertSame('max_value', $condition->getIntervalEndColumn());
    }

    public function testException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires three operands.");
        BetweenColumnsCondition::fromArrayDefinition('between', []);
    }
}
