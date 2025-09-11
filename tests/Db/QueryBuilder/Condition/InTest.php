<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\In;

/**
 * @group db
 */
final class InTest extends TestCase
{
    public function testConstructor(): void
    {
        $inCondition = new In('id', [1, 2, 3]);

        $this->assertSame('id', $inCondition->column);
        $this->assertSame([1, 2, 3], $inCondition->values);
    }

    public function testFromArrayDefinition(): void
    {
        $inCondition = In::fromArrayDefinition('IN', ['id', [1, 2, 3]]);

        $this->assertSame('id', $inCondition->column);
        $this->assertSame([1, 2, 3], $inCondition->values);
    }

    public function testFromArrayDefinitionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'IN' requires two operands.");

        In::fromArrayDefinition('IN', []);
    }

    public function testFromArrayDefinitionExceptionColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'IN' requires column to be string, ExpressionInterface or iterable.");

        In::fromArrayDefinition('IN', [1, [1, 2, 3]]);
    }

    public function testFromArrayDefinitionExceptionValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'IN' requires values to be iterable or QueryInterface."
        );

        In::fromArrayDefinition('IN', ['id', false]);
    }
}
