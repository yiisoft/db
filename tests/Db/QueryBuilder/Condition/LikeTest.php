<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\LikeMode;

/**
 * @group db
 */
final class LikeTest extends TestCase
{
    public function testConstructor(): void
    {
        $likeCondition = new Like('id', 'test');

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('test', $likeCondition->value);
        $this->assertNull($likeCondition->caseSensitive);
        $this->assertTrue($likeCondition->escape);
        $this->assertSame(LikeMode::Contains, $likeCondition->mode);
    }

    public function testFromArrayDefinition(): void
    {
        $likeCondition = Like::fromArrayDefinition('LIKE', ['id', 'test']);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('test', $likeCondition->value);
        $this->assertNull($likeCondition->caseSensitive);
        $this->assertTrue($likeCondition->escape);
        $this->assertSame(LikeMode::Contains, $likeCondition->mode);
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

    public function testFromArrayDefinitionWithEscape(): void
    {
        $likeCondition = Like::fromArrayDefinition('LIKE', ['id', 'test', 'escape' => false]);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('test', $likeCondition->value);
        $this->assertFalse($likeCondition->escape);
    }

    #[TestWith([null])]
    #[TestWith([true])]
    #[TestWith([false])]
    public function testFromArrayDefinitionCaseSensitive(?bool $caseSensitive): void
    {
        $likeCondition = Like::fromArrayDefinition('LIKE', ['id', 'test', 'caseSensitive' => $caseSensitive]);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('test', $likeCondition->value);
        $this->assertSame($caseSensitive, $likeCondition->caseSensitive);
    }

    #[TestWith([LikeMode::Contains])]
    #[TestWith([LikeMode::StartsWith])]
    #[TestWith([LikeMode::EndsWith])]
    #[TestWith([LikeMode::Contains])]
    public function testFromArrayDefinitionWithMode(LikeMode $mode): void
    {
        $likeCondition = Like::fromArrayDefinition('LIKE', ['id', 'test', 'mode' => $mode]);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('test', $likeCondition->value);
        $this->assertSame($mode, $likeCondition->mode);
    }

    public function testConstructorWithMode(): void
    {
        $likeCondition = new Like('id', 'test', null, true, LikeMode::StartsWith);

        $this->assertSame('id', $likeCondition->column);
        $this->assertSame('test', $likeCondition->value);
        $this->assertSame(LikeMode::StartsWith, $likeCondition->mode);
    }

    public function testFromArrayDefinitionWithInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Operator "LIKE" requires "mode" to be an instance of Yiisoft\Db\QueryBuilder\Condition\LikeMode. Got string.'
        );

        Like::fromArrayDefinition('LIKE', ['id', 'test', 'mode' => 'invalid']);
    }
}
