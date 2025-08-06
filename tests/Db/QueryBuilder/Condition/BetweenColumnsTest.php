<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\BetweenColumns;

/**
 * @group db
 */
final class BetweenColumnsTest extends TestCase
{
    public function testConstructor(): void
    {
        $betweenColumnsCondition = new BetweenColumns(42, 'min_value', 'max_value');

        $this->assertSame(42, $betweenColumnsCondition->value);
        $this->assertSame('min_value', $betweenColumnsCondition->intervalStartColumn);
        $this->assertSame('max_value', $betweenColumnsCondition->intervalEndColumn);
    }

    public function testFromArrayDefinition(): void
    {
        $betweenColumnsCondition = BetweenColumns::fromArrayDefinition(
            'BETWEEN',
            [42, 'min_value', 'max_value']
        );

        $this->assertSame(42, $betweenColumnsCondition->value);
        $this->assertSame('min_value', $betweenColumnsCondition->intervalStartColumn);
        $this->assertSame('max_value', $betweenColumnsCondition->intervalEndColumn);
    }

    public function testFromArrayDefinitionExceptionWithoutOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires three operands.");

        BetweenColumns::fromArrayDefinition('between', []);
    }

    public function testFromArrayDefinitionExceptionOperandsValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'between' requires value to be array, int, string, Iterator or ExpressionInterface."
        );

        BetweenColumns::fromArrayDefinition('between', [false, 'min_value', 'max_value']);
    }

    public function testFromArrayDefinitionExceptionOperandsIntervalStartColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'between' requires interval start column to be string or ExpressionInterface."
        );

        BetweenColumns::fromArrayDefinition('between', [42, false, 'max_value']);
    }

    public function testFromArrayDefinitionExceptionOperandsIntervalEndColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'between' requires interval end column to be string or ExpressionInterface."
        );

        BetweenColumns::fromArrayDefinition('between', [42, 'min_value', false]);
    }
}
