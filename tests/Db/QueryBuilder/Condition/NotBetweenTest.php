<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\NotBetween;

/**
 * @group db
 */
final class NotBetweenTest extends TestCase
{
    public function testConstructor(): void
    {
        $condition = new NotBetween('date', 1, 2);

        $this->assertSame('date', $condition->column);
        $this->assertSame(1, $condition->intervalStart);
        $this->assertSame(2, $condition->intervalEnd);
    }

    public function testFromArrayDefinition(): void
    {
        $condition = NotBetween::fromArrayDefinition('NOT BETWEEN', ['date', 1, 2]);

        $this->assertSame('date', $condition->column);
        $this->assertSame(1, $condition->intervalStart);
        $this->assertSame(2, $condition->intervalEnd);
    }

    public function testFromArrayDefinitionExceptionWithoutOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'not between' requires three operands.");

        NotBetween::fromArrayDefinition('not between', []);
    }

    public function testFromArrayDefinitionExceptionColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'not between' requires column to be string or ExpressionInterface.");

        NotBetween::fromArrayDefinition('not between', [1, 'min_value', 'max_value']);
    }
}
