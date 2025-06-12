<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\WhenClause;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class CaseExpressionTest extends TestCase
{
    use TestTrait;

    public static function dataCase(): array
    {
        return [
            'null' => [null],
            'string' => ['field = 1'],
            'expression' => [new Expression('field = 1')],
            'boolean' => [true],
            'float' => [2.3],
            'int' => [1],
            'array' => [['=', 'field', 1]],
        ];
    }

    #[DataProvider('dataCase')]
    public function testConstruct(
        array|bool|ExpressionInterface|float|int|string|null $case
    ): void {
        $expression = new CaseExpression($case);

        $this->assertSame($case, $expression->getCase());
    }

    #[DataProvider('dataCase')]
    public function testCase(array|bool|ExpressionInterface|float|int|string|null $case): void
    {
        $expression = new CaseExpression();

        $this->assertNull($expression->getCase());

        $expression->case($case);

        $this->assertSame($case, $expression->getCase());
    }

    public function testWhen(): void
    {
        $expression = new CaseExpression();

        $this->assertSame([], $expression->getWhen());

        $expression->addWhen('field = 1', 'result1');
        $expression->addWhen('field = 2', 'result2');

        Assert::arraysEquals(
            [
                new WhenClause('field = 1', 'result1'),
                new WhenClause('field = 2', 'result2'),
            ],
            $expression->getWhen(),
        );

        $expression->setWhen([]);

        $this->assertSame([], $expression->getWhen());

        $expression->setWhen([new WhenClause('field = 3', 'result3')]);

        Assert::arraysEquals([new WhenClause('field = 3', 'result3')], $expression->getWhen());
    }

    public function testElse(): void
    {
        $expression = new CaseExpression();

        $this->assertFalse($expression->hasElse());
        $this->assertNull($expression->getElse());

        $expression->else(null);

        $this->assertTrue($expression->hasElse());
        $this->assertNull($expression->getElse());

        $expression->else('result');

        $this->assertTrue($expression->hasElse());
        $this->assertSame('result', $expression->getElse());
    }

    public function testWhenClause(): void
    {
        $when = new WhenClause('field = 1', 'result1');

        $this->assertSame('field = 1', $when->condition);
        $this->assertSame('result1', $when->result);
    }
}
