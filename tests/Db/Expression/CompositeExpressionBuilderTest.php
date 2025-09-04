<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\CompositeExpression;
use Yiisoft\Db\Expression\CompositeExpressionBuilder;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class CompositeExpressionBuilderTest extends TestCase
{
    use TestTrait;

    public function testBase(): void
    {
        $builder = new CompositeExpressionBuilder($this->getConnection()->getQueryBuilder());
        $expression = new CompositeExpression([
            'x=1',
            new Expression('AND y=:p', ['p' => 2]),
        ]);

        $params = [];
        $sql = $builder->build($expression, $params);

        $this->assertSame('x=1 AND y=:p', $sql);
        $this->assertEquals(['p' => 2], $params);
    }

    public function testCustomSeparator(): void
    {
        $builder = new CompositeExpressionBuilder($this->getConnection()->getQueryBuilder());
        $expression = new CompositeExpression(
            ['x=1', 'y=2'],
            ' AND ',
        );

        $sql = $builder->build($expression);

        $this->assertSame('x=1 AND y=2', $sql);
    }
}
