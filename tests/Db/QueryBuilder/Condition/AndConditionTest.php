<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\AndCondition;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class AndConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $andCondition = new AndCondition(['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $andCondition->getExpressions());
    }

    public function testGetOperator(): void
    {
        $andCondition = new AndCondition(['a' => 1, 'b' => 2]);

        $this->assertSame('AND', $andCondition->getOperator());
    }
}
