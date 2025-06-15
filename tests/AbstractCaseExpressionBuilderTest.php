<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
abstract class AbstractCaseExpressionBuilderTest extends TestCase
{
    use TestTrait;

    public static function buildProvider(): array
    {
        return [
            'with case expression' => [
                (new CaseExpression('expression'))
                    ->addWhen(1, 'a')
                    ->addWhen(2, new Expression('1 + 2'))
                    ->else('c'),
                'CASE expression WHEN 1 THEN :qp0 WHEN 2 THEN 1 + 2 ELSE :qp1 END',
                [
                    ':qp0' => new Param('a', DataType::STRING),
                    ':qp1' => new Param('c', DataType::STRING),
                ],
            ],
            'without case expression' => [
                (new CaseExpression())
                    ->addWhen(['=', 'column_name', 1], 'a')
                    ->addWhen('column_name = 2', (new Query(self::getDb()))->select(3)),
                DbHelper::replaceQuotes(
                    <<<SQL
                    CASE WHEN [[column_name]] = :qp0 THEN :qp1 WHEN column_name = 2 THEN (SELECT 3) END
                    SQL,
                    static::getDriverName(),
                ),
                [
                    ':qp0' => 1,
                    ':qp1' => new Param('a', DataType::STRING),
                ],
            ],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(CaseExpression $case, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];

        $this->assertSame($expected, $qb->buildExpression($case, $params));
        $this->assertEquals($expectedParams, $params);
    }

    public function testBuildEmpty(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $case = new CaseExpression();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The CASE expression must have at least one WHEN clause.');

        $qb->buildExpression($case, $params);
    }
}
