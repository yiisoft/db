<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Like;

/**
 * @group db
 */
final class LikeTest extends TestCase
{
    public function testConstructor(): void
    {
        $likeCondition = new Like('id', 'LIKE', 'test');

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('LIKE', $likeCondition->operator);
        $this->assertSame('test', $likeCondition->value);
        $this->assertNull($likeCondition->caseSensitive);
    }

    public function testFromArrayDefinition(): void
    {
        $likeCondition = Like::fromArrayDefinition('LIKE', ['id', 'test']);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('LIKE', $likeCondition->operator);
        $this->assertSame('test', $likeCondition->value);
        $this->assertNull($likeCondition->caseSensitive);
    }

    public function testFromArrayDefinitionException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'LIKE' requires two operands.");

        Like::fromArrayDefinition('LIKE', []);
    }

    public function testFromArrayDefinitionExceptionColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'LIKE' requires column to be string or ExpressionInterface.");

        Like::fromArrayDefinition('LIKE', [false, 'test']);
    }

    public function testFromArrayDefinitionExceptionValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Operator 'LIKE' requires value to be string, array, Iterator or ExpressionInterface."
        );

        Like::fromArrayDefinition('LIKE', ['id', false]);
    }

    public function testFromArrayDefinitionSetEscapingReplacements(): void
    {
        $likeCondition = Like::fromArrayDefinition('LIKE', ['id', 'test', ['%' => '\%', '_' => '\_']]);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('LIKE', $likeCondition->operator);
        $this->assertSame('test', $likeCondition->value);
        $this->assertSame(['%' => '\%', '_' => '\_'], $likeCondition->getEscapingReplacements());
    }

    public function testSetEscapingReplacements(): void
    {
        $likeCondition = new Like('id', 'LIKE', 'test');
        $likeCondition->setEscapingReplacements(['%' => '\%', '_' => '\_']);

        $this->assertSame(['%' => '\%', '_' => '\_'], $likeCondition->getEscapingReplacements());
    }

    #[TestWith([null])]
    #[TestWith([true])]
    #[TestWith([false])]
    public function testFromArrayDefinitionCaseSensitive(?bool $caseSensitive): void
    {
        $likeCondition = Like::fromArrayDefinition('LIKE', ['id', 'test', 'caseSensitive' => $caseSensitive]);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('LIKE', $likeCondition->operator);
        $this->assertSame('test', $likeCondition->value);
        $this->assertSame($caseSensitive, $likeCondition->caseSensitive);
    }
}
