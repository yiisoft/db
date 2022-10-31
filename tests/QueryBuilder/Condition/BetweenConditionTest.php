<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder\Conditions;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Conditions\BetweenCondition;

/**
 * @group db
 */
final class BetweenConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $condition = new BetweenCondition('date', 'BETWEEN', 1, 2);
        $this->assertSame('date', $condition->getColumn());
        $this->assertSame('BETWEEN', $condition->getOperator());
        $this->assertSame(1, $condition->getIntervalStart());
        $this->assertSame(2, $condition->getIntervalEnd());
    }
}
