<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\NotCondition;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class NotConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $notCondition = new NotCondition('id = 1');

        $this->assertSame('id = 1', $notCondition->getCondition());
    }

    public function testFromArrayDefinition(): void
    {
        $notCondition = NotCondition::fromArrayDefinition('NOT', ['id = 1']);

        $this->assertSame('id = 1', $notCondition->getCondition());
    }

    public function testFromArrayDefinitionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'NOT' requires exactly one operand.");

        NotCondition::fromArrayDefinition('NOT', []);
    }

    public function testFromArrayDefinitionExceptionCondition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'NOT' requires condition to be array, string, null or ExpressionInterface."
        );

        NotCondition::fromArrayDefinition('NOT', [false]);
    }
}
