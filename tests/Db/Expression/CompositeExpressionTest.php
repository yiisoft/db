<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\CompositeExpression;
use Yiisoft\Db\Expression\Expression;

/**
 * @group db
 */
final class CompositeExpressionTest extends TestCase
{
    public function testBase(): void
    {
        $expressions = [
            'test',
            new Expression(''),
        ];
        $expression = new CompositeExpression(...$expressions);

        $this->assertSame($expressions, $expression->expressions);
    }
}
