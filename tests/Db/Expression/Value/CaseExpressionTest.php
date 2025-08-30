<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Statement\CaseExpression;
use Yiisoft\Db\Expression\Statement\WhenClause;
use Yiisoft\Db\Schema\Column\IntegerColumn;
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
    public function testConstruct(mixed $case): void
    {
        $expression = new CaseExpression($case);

        $this->assertSame($case, $expression->getCase());
        $this->assertSame('', $expression->getCaseType());
        $this->assertSame([], $expression->getWhen());
    }

    public function testConstructType(): void
    {
        $expression = new CaseExpression(caseType: 'int');

        $this->assertNull($expression->getCase());
        $this->assertSame('int', $expression->getCaseType());

        $intCol = new IntegerColumn();
        $expression = new CaseExpression(caseType: $intCol);
        $this->assertNull($expression->getCase());
        $this->assertSame($intCol, $expression->getCaseType());
        $this->assertSame([], $expression->getWhen());
    }

    public function testConstructorWhenClauses()
    {
        // Test with one when clauses
        $whenClause = new WhenClause('field = 1', 'result1');
        $expression = new CaseExpression(when: $whenClause);

        $this->assertNull($expression->getCase());
        $this->assertSame('', $expression->getCaseType());
        $this->assertSame(['when' => $whenClause], $expression->getWhen());

        // Test with multiple when clauses
        $whenClauses = [
            'when0' => new WhenClause('field = 1', 'result1'),
            'when1' => new WhenClause('field = 2', 'result2'),
        ];
        $expression = new CaseExpression(...$whenClauses);

        $this->assertNull($expression->getCase());
        $this->assertSame('', $expression->getCaseType());
        $this->assertSame($whenClauses, $expression->getWhen());
    }

    #[DataProvider('dataCase')]
    public function testCase(mixed $case): void
    {
        $expression = new CaseExpression();

        $this->assertNull($expression->getCase());

        $expression->case($case);

        $this->assertSame($case, $expression->getCase());
    }

    public function testCaseType(): void
    {
        $expression = new CaseExpression();

        $this->assertSame('', $expression->getCaseType());

        $expression->caseType('int');

        $this->assertSame('int', $expression->getCaseType());

        $intCol = new IntegerColumn();
        $expression->caseType($intCol);

        $this->assertSame($intCol, $expression->getCaseType());
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

        $expression->setWhen();

        $this->assertSame([], $expression->getWhen());

        $whenClauses = [new WhenClause('field = 3', 'result3')];
        $expression->setWhen(...$whenClauses);

        $this->assertSame($whenClauses, $expression->getWhen());

        $whenClauses = [
            new WhenClause('field = 3', 'result3'),
            new WhenClause('field = 4', 'result4'),
        ];
        $expression->setWhen(...$whenClauses);

        $this->assertSame($whenClauses, $expression->getWhen());
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
