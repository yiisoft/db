<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\DDLQueryBuilder;
use Yiisoft\Db\QueryBuilder\DMLQueryBuilder;
use Yiisoft\Db\QueryBuilder\DQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\Schema;

/**
 * @group db
 */
final class FromTest extends TestCase
{
    /** @dataProvider fromCases */
    public function testBasic(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        $query = $this->createQuery()->from($table);

        [$sql, $params] = $this->build($query);

        $this->assertSame($expectedSql, $sql);
        $this->assertSame($expectedParams, $params);
    }

    private function fromCases(): array
    {
        return [
            ['table1', 'SELECT * FROM "table1"'],
            [['table1'], 'SELECT * FROM "table1"'],
            [new Expression('table2'), 'SELECT * FROM table2'],
            [[new Expression('table2')], 'SELECT * FROM table2'],
            [['alias' => 'table3'], 'SELECT * FROM "table3" "alias"'],
            [['alias' => new Expression('table4')], 'SELECT * FROM table4 "alias"'],
            [
                ['alias' => new Expression('func(:param1, :param2)',  ['param1' => 'A', 'param2' => 'B'])],
                'SELECT * FROM func(:param1, :param2) "alias"',
                ['param1' => 'A', 'param2' => 'B'],
            ],
        ];
    }

    private function build(Query $query): array
    {
        $cm = \Closure::fromCallable([$this, 'createMock']);
        $qb = new class ($cm,) extends QueryBuilder {
            public function __construct( \Closure $cm)
            {
                $quoter = new Quoter('"', '"');
                /** @var Schema $schema */
                $schema = $cm(Schema::class);
                /** @var DDLQueryBuilder $ddlBuilder */
                $ddlBuilder = $cm(DDLQueryBuilder::class);
                /** @var DMLQueryBuilder $dmlBuilder */
                $dmlBuilder = $cm(DMLQueryBuilder::class);

                $dqlBuilder = new class (
                    $this,
                    $quoter,
                    $schema,
                ) extends DQLQueryBuilder {};

                parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder);
            }
        };

        return $qb->build($query);
    }

    private function createQuery(): Query
    {
        return (new Query($this->createMock(Connection::class)));
    }
}
