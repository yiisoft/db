<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LikeConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $likeCondition = new LikeCondition('id', 'LIKE', 'test');

        $this->assertSame('id', $likeCondition->getColumn());
        $this->assertSame('LIKE', $likeCondition->getOperator());
        $this->assertSame('test', $likeCondition->getValue());
    }

    public function testFromArrayDefinition(): void
    {
        $likeCondition = LikeCondition::fromArrayDefinition('LIKE', ['id', 'test']);

        $this->assertSame('id', $likeCondition->getColumn());
        $this->assertSame('LIKE', $likeCondition->getOperator());
        $this->assertSame('test', $likeCondition->getValue());
    }

    public function testFromArrayDefinitionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'LIKE' requires two operands.");

        LikeCondition::fromArrayDefinition('LIKE', []);
    }

    public function testFromArrayDefinitionExceptionColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'LIKE' requires column to be string or ExpressionInterface.");

        LikeCondition::fromArrayDefinition('LIKE', [false, 'test']);
    }

    public function testFromArrayDefinitionExceptionValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'LIKE' requires value to be string, array, Iterator or ExpressionInterface."
        );

        LikeCondition::fromArrayDefinition('LIKE', ['id', false]);
    }

    public function testFromArrayDefinitionSetEscapingReplacements(): void
    {
        $likeCondition = LikeCondition::fromArrayDefinition('LIKE', ['id', 'test', ['%' => '\%', '_' => '\_']]);

        $this->assertSame('id', $likeCondition->getColumn());
        $this->assertSame('LIKE', $likeCondition->getOperator());
        $this->assertSame('test', $likeCondition->getValue());
        $this->assertSame(['%' => '\%', '_' => '\_'], $likeCondition->getEscapingReplacements());
    }

    public function testSetEscapingReplacements(): void
    {
        $likeCondition = new LikeCondition('id', 'LIKE', 'test');
        $likeCondition->setEscapingReplacements(['%' => '\%', '_' => '\_']);

        $this->assertSame(['%' => '\%', '_' => '\_'], $likeCondition->getEscapingReplacements());
    }
}
