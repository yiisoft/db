<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\ArrayExpression;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ArrayExpressionTest extends TestCase
{
    public function testConstruct(): void
    {
        $expression = new ArrayExpression(['a', 'b', 'c'], 'string', 1);

        $this->assertSame(['a', 'b', 'c'], $expression->getValue());
        $this->assertSame('string', $expression->getType());
        $this->assertSame(1, $expression->getDimension());
    }

    public function testOffsetExists(): void
    {
        $expression = new ArrayExpression(['a', 'b', 'c'], 'string', 1);

        $this->assertTrue(isset($expression[0]));
        $this->assertFalse(isset($expression[3]));
    }

    public function testOffsetGet(): void
    {
        $expression = new ArrayExpression(['a', 'b', 'c'], 'string', 1);

        $this->assertSame('a', $expression[0]);
        $this->assertSame('b', $expression[1]);
        $this->assertSame('c', $expression[2]);
    }

    public function testOffsetSet(): void
    {
        $expression = new ArrayExpression(['a', 'b', 'c'], 'string', 1);

        $expression[0] = 'd';
        $expression[1] = 'e';
        $expression[2] = 'f';

        $this->assertSame('d', $expression[0]);
        $this->assertSame('e', $expression[1]);
        $this->assertSame('f', $expression[2]);
    }

    public function testOffsetUnset(): void
    {
        $expression = new ArrayExpression(['a', 'b', 'c'], 'string', 1);

        unset($expression[0], $expression[1], $expression[2]);

        $this->assertFalse(isset($expression[0]));
        $this->assertFalse(isset($expression[1]));
        $this->assertFalse(isset($expression[2]));
    }

    public function testCount(): void
    {
        $expression = new ArrayExpression(['a', 'b', 'c'], 'string', 1);

        $this->assertCount(3, $expression);
    }

    public function testGetIterator(): void
    {
        $expression = new ArrayExpression(['a', 'b', 'c'], 'string', 1);


        $this->assertSame(['a', 'b', 'c'], iterator_to_array($expression->getIterator()));
    }

    public function testGetIteratorException(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The ArrayExpression value must be an array.');

        $expression = new ArrayExpression('c', 'string', 2);

        $expression->getIterator();
    }

    public function testGetKeyException(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The ArrayExpression offset must be an integer.');

        $expression = new ArrayExpression([1]);
        $expression['a'];
    }
}
