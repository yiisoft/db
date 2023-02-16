<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class InConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $inCondition = new InCondition('id', 'IN', [1, 2, 3]);

        $this->assertSame('id', $inCondition->getColumn());
        $this->assertSame('IN', $inCondition->getOperator());
        $this->assertSame([1, 2, 3], $inCondition->getValues());
    }

    public function testFromArrayDefinition(): void
    {
        $inCondition = InCondition::fromArrayDefinition('IN', ['id', [1, 2, 3]]);

        $this->assertSame('id', $inCondition->getColumn());
        $this->assertSame('IN', $inCondition->getOperator());
        $this->assertSame([1, 2, 3], $inCondition->getValues());
    }

    public function testFromArrayDefinitionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'IN' requires two operands.");

        InCondition::fromArrayDefinition('IN', []);
    }

    public function testFromArrayDefinitionExceptionColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'IN' requires column to be string, array or Iterator.");

        InCondition::fromArrayDefinition('IN', [1, [1, 2, 3]]);
    }

    public function testFromArrayDefinitionExceptionValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'IN' requires values to be array, Iterator, int or QueryInterface."
        );

        InCondition::fromArrayDefinition('IN', ['id', false]);
    }
}
