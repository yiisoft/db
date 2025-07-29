<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\Equals;

use function PHPUnit\Framework\assertSame;

/**
 * @group db
 */
final class EqualsTest extends TestCase
{
    public function testFromArrayDefinition(): void
    {
        $condition = Equals::fromArrayDefinition('EQUALS', ['id', 25]);

        assertSame('id', $condition->column);
        assertSame(25, $condition->value);
    }

    public function testFromArrayDefinitionMissingFirstOperand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator '=' requires first operand as column.");

        Equals::fromArrayDefinition('=', []);
    }

    public function testFromArrayDefinitionMissingSecondOperand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'EQUALS' requires second operand as value.");

        Equals::fromArrayDefinition('EQUALS', ['column']);
    }

    public static function dataFromArrayDefinitionInvalidColumn(): iterable
    {
        yield [123];
        yield [['hello']];
        yield [null];
    }

    #[DataProvider('dataFromArrayDefinitionInvalidColumn')]
    public function testFromArrayDefinitionInvalidColumn(mixed $column): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'EQUALS' requires column to be string or ExpressionInterface.");

        Equals::fromArrayDefinition('EQUALS', [$column, 'value']);
    }
}
