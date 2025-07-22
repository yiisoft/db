<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Not;

/**
 * @group db
 */
final class NotTest extends TestCase
{
    public function testConstructor(): void
    {
        $notCondition = new Not('id = 1');

        $this->assertSame('id = 1', $notCondition->condition);
    }

    public function testFromArrayDefinition(): void
    {
        $notCondition = Not::fromArrayDefinition('NOT', ['id = 1']);

        $this->assertSame('id = 1', $notCondition->condition);
    }

    public function testFromArrayDefinitionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'NOT' requires exactly one operand.");

        Not::fromArrayDefinition('NOT', []);
    }

    public function testFromArrayDefinitionExceptionCondition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'NOT' requires condition to be array, string, null or ExpressionInterface."
        );

        Not::fromArrayDefinition('NOT', [false]);
    }
}
