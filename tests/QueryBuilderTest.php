<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Closure;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Mock;

/**
 * @group db
 */
final class QueryBuilderTest extends TestCase
{
    private QueryBuilderInterface $queryBuilder;
    private Mock $mock;

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = new Mock();
        $this->queryBuilder = $this->mock->queryBuilder();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->queryBuilder, $this->mock);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::batchInsert()
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $value,
        string|null $expected,
        array $expectedParams = []
    ): void {
        $params = [];
        $sql = $this->queryBuilder->batchInsert($table, $columns, $value, $params);

        $this->assertSame($expected, $sql);
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildConditions()
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $conditions,
        string $expected,
        array $expectedParams = []
    ): void {
        $query = $this->mock->query()->where($conditions);
        [$sql, $params] = $this->queryBuilder->build($query);

        $this->assertSame(
            'SELECT *' . (
                empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes(
                    $expected,
                    $this->mock->getDriverName(),
                )
            ),
            $sql,
        );
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildFilterCondition()
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $query = $this->mock->query()->filterWhere($condition);
        [$sql, $params] = $this->queryBuilder->build($query);

        $this->assertSame(
            'SELECT *' . (
                empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes(
                    $expected,
                    $this->mock->getDriverName(),
                )
            ),
            $sql,
        );
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildFrom()
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $params = [];
        $sql = $this->queryBuilder->buildFrom([$table], $params);
        $replacedQuotes = DbHelper::replaceQuotes($expected, $this->mock->getDriverName());

        $this->assertIsString($replacedQuotes);
        $this->assertSame('FROM ' . $replacedQuotes, $sql);
    }

    /**
     * This test contains three select queries connected with UNION and UNION ALL constructions.
     * It could be useful to use "phpunit --group=db --filter testBuildUnion" command for run it.
     */
    public function testBuildUnion(): void
    {
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            (SELECT [[id]] FROM [[TotalExample]] [[t1]] WHERE (w > 0) AND (x < 2)) UNION ( SELECT [[id]] FROM [[TotalTotalExample]] [[t2]] WHERE w > 5 ) UNION ALL ( SELECT [[id]] FROM [[TotalTotalExample]] [[t3]] WHERE w = 3 )
            SQL,
            $this->mock->getDriverName(),
        );

        $secondQuery = $this->mock
            ->query()
            ->select('id')
            ->from('TotalTotalExample t2')
            ->where('w > 5');

        $thirdQuery = $this->mock
            ->query()
            ->select('id')
            ->from('TotalTotalExample t3')
            ->where('w = 3');

        $query = $this->mock
            ->query()
            ->select('id')
            ->from('TotalExample t1')
            ->where(['and', 'w > 0', 'x < 2'])
            ->union($secondQuery)
            ->union($thirdQuery, true);

        [$actualQuerySql, $queryParams] = $this->queryBuilder->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame([], $queryParams);
    }

    public function testBuildWithQuery(): void
    {
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            WITH a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1), a2 AS ((SELECT [[id]] FROM [[t2]] INNER JOIN [[a1]] ON t2.id = a1.id WHERE expr = 2) UNION ( SELECT [[id]] FROM [[t3]] WHERE expr = 3 )) SELECT * FROM [[a2]]
            SQL,
            $this->mock->getDriverName(),
        );

        $with1Query = $this->mock
            ->query()
            ->select('id')
            ->from('t1')
            ->where('expr = 1');

        $with2Query = $this->mock
            ->query()
            ->select('id')
            ->from('t2')
            ->innerJoin('a1', 't2.id = a1.id')
            ->where('expr = 2');

        $with3Query = $this->mock
            ->query()
            ->select('id')
            ->from('t3')
            ->where('expr = 3');

        $query = $this->mock
            ->query()
            ->withQuery($with1Query, 'a1')
            ->withQuery($with2Query->union($with3Query), 'a2')
            ->from('a2');

        [$actualQuerySql, $queryParams] = $this->queryBuilder->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame([], $queryParams);
    }

    public function testBuildWithQueryRecursive(): void
    {
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            WITH RECURSIVE a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1) SELECT * FROM [[a1]]
            SQL,
            $this->mock->getDriverName(),
        );

        $with1Query = $this->mock
            ->query()
            ->select('id')
            ->from('t1')
            ->where('expr = 1');

        $query = $this->mock
            ->query()
            ->withQuery($with1Query, 'a1', true)
            ->from('a1');

        [$actualQuerySql, $queryParams] = $this->queryBuilder->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame([], $queryParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildWhereExists()
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $expectedQueryParams = [];
        $subQuery = $this->mock->query()->select('1')->from('Website w');
        $query = $this->mock->query()->select('id')->from('TotalExample t')->where([$cond, $subQuery]);

        [$actualQuerySql, $actualQueryParams] = $this->queryBuilder->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame($expectedQueryParams, $actualQueryParams);
    }

    public function testBuildWhereExistsWithArrayParameters(): void
    {
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (([[w]].[[merchant_id]]=:qp0) AND ([[w]].[[user_id]]=:qp1)))) AND ([[t]].[[some_column]]=:qp2)
            SQL,
            $this->mock->getDriverName(),
        );

        $expectedQueryParams = [':qp0' => 6, ':qp1' => 210, ':qp2' => 'asd'];

        $subQuery = $this->mock
            ->query()
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere(['w.merchant_id' => 6, 'w.user_id' => 210]);

        $query = $this->mock
            ->query()
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere(['t.some_column' => 'asd']);

        [$actualQuerySql, $queryParams] = $this->queryBuilder->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame($expectedQueryParams, $queryParams);
    }

    public function testBuildWhereExistsWithParameters(): void
    {
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (w.merchant_id = :merchant_id))) AND (t.some_column = :some_value)
            SQL,
            $this->mock->getDriverName(),
        );

        $expectedQueryParams = [':some_value' => 'asd', ':merchant_id' => 6];

        $subQuery = $this->mock
            ->query()
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere('w.merchant_id = :merchant_id', [':merchant_id' => 6]);

        $query = $this->mock
            ->query()
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere('t.some_column = :some_value', [':some_value' => 'asd']);

        [$actualQuerySql, $queryParams] = $this->queryBuilder->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame($expectedQueryParams, $queryParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::createDropIndex()
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $this->assertSame($this->mock->quoter()->quoteSql($sql), $builder($this->queryBuilder));
    }

    public function testComplexSelect(): void
    {
        $expressionString = DbHelper::replaceQuotes(
            <<<SQL
            case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]]
            SQL,
            $this->mock->getDriverName(),
        );

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT [[t]].[[id]] AS [[ID]], [[gsm]].[[username]] AS [[GSM]], [[part]].[[Part]], [[t]].[[Part_Cost]] AS [[Part Cost]], st_x(location::geometry) AS [[lon]], case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]] FROM [[tablename]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertIsString($expressionString);

        $query = $this->mock
            ->query()
            ->select(
                [
                    'ID' => 't.id',
                    'gsm.username as GSM',
                    'part.Part',
                    'Part Cost' => 't.Part_Cost',
                    'st_x(location::geometry) as lon',
                    new Expression($expressionString),
                ]
            )
            ->from('tablename');

        [$sql, $params] = $this->queryBuilder->build($query);

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::delete()
     */
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $actualSQL = $this->queryBuilder->delete($table, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/10869}
     */
    public function testFromIndexHint(): void
    {
        $query = $this->mock->query()->from([new Expression('{{%user}} USE INDEX (primary)')]);

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM {{%user}} USE INDEX (primary)
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = $this->mock
            ->query()
            ->from([new Expression('{{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)')])
            ->leftJoin(['p' => 'profile'], 'user.id = profile.user_id USE INDEX (i2)');

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM {{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1) LEFT JOIN [[profile]] [[p]] ON user.id = profile.user_id USE INDEX (i2)
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testFromSubquery(): void
    {
        /* subquery */
        $subquery = $this->mock->query()->from('user')->where('account_id = accounts.id');
        $query = $this->mock->query()->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = accounts.id) [[activeusers]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* subquery with params */
        $subquery = $this->mock->query()->from('user')->where('account_id = :id', ['id' => 1]);
        $query = $this->mock->query()->from(['activeusers' => $subquery])->where('abc = :abc', ['abc' => 'abc']);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = :id) [[activeusers]] WHERE abc = :abc
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame(['abc' => 'abc', 'id' => 1], $params);

        /* simple subquery */
        $subquery = '(SELECT * FROM user WHERE account_id = accounts.id)';
        $query = $this->mock->query()->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM user WHERE account_id = accounts.id) [[activeusers]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testGroupBy(): void
    {
        /* simple string */
        $query = $this->mock->query()->select('*')->from('operations')->groupBy('name, date');

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = $this->mock->query()->select('*')->from('operations')->groupBy(['name', 'date']);

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = $this->mock
            ->query()
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->groupBy(new Expression('SUBSTR(name, 0, 1), x'));

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] WHERE account_id = accounts.id GROUP BY SUBSTR(name, 0, 1), x
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = $this->mock
            ->query()
            ->select('*')
            ->from('operations')
            ->groupBy(new Expression('SUBSTR(name, 0, :to), x', [':to' => 4]));

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY SUBSTR(name, 0, :to), x
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame([':to' => 4], $params);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15653}
     */
    public function testIssue15653(): void
    {
        $query = $this->mock->query()->from('admin_user')->where(['is_deleted' => false]);
        $query->where([])->andWhere(['in', 'id', ['1', '0']]);

        [$sql, $params] = $this->queryBuilder->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[admin_user]] WHERE [[id]] IN (:qp0, :qp1)
                SQL,
                $this->mock->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([':qp0' => '1', ':qp1' => '0'], $params);
    }

    public function testOrderBy(): void
    {
        /* simple string */
        $query = $this->mock->query()->select('*')->from('operations')->orderBy('name ASC, date DESC');

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = $this->mock->query()->select('*')->from('operations')->orderBy(['name' => SORT_ASC, 'date' => SORT_DESC]);

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = $this->mock
            ->query()
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->orderBy(new Expression('SUBSTR(name, 3, 4) DESC, x ASC'));

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] WHERE account_id = accounts.id ORDER BY SUBSTR(name, 3, 4) DESC, x ASC
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = $this->mock
            ->query()
            ->select('*')
            ->from('operations')
            ->orderBy(new Expression('SUBSTR(name, 3, :to) DESC, x ASC', [':to' => 4]));

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY SUBSTR(name, 3, :to) DESC, x ASC
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame([':to' => 4], $params);
    }

    public function testRenameColumn(): void
    {
        $sql = $this->queryBuilder->renameColumn('alpha', 'string_identifier', 'string_identifier_test');
        $this->assertSame(
            <<<SQL
            ALTER TABLE `alpha` RENAME COLUMN `string_identifier` TO `string_identifier_test`
            SQL,
            $sql,
        );

        $sql = $this->queryBuilder->renameColumn('alpha', 'string_identifier_test', 'string_identifier');
        $this->assertSame(
            <<<SQL
            ALTER TABLE `alpha` RENAME COLUMN `string_identifier_test` TO `string_identifier`
            SQL,
            $sql,
        );
    }

    public function testRenameTable(): void
    {
        $sql = $this->queryBuilder->renameTable('table_from', 'table_to');

        $this->assertSame(
            <<<SQL
            RENAME TABLE `table_from` TO `table_to`
            SQL,
            $sql,
        );
    }

    public function testSelectExpression(): void
    {
        $query = $this->mock->query()->select(new Expression('1 AS ab'))->from('tablename');

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT 1 AS ab FROM [[tablename]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = $this->mock
            ->query()
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = $this->mock
            ->query()
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $this->queryBuilder->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT SUBSTR(name, 0, :len) FROM [[tablename]]
            SQL,
            $this->mock->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame([':len' => 4], $params);
    }

    public function testSelectSubquery(): void
    {
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT *, (SELECT COUNT(*) FROM [[operations]] WHERE account_id = accounts.id) AS [[operations_count]] FROM [[accounts]]
            SQL,
            $this->mock->getDriverName(),
        );

        $subquery = $this->mock
            ->query()
            ->select('COUNT(*)')
            ->from('operations')
            ->where('account_id = accounts.id');

        $query = $this->mock
            ->query()
            ->select('*')
            ->from('accounts')
            ->addSelect(['operations_count' => $subquery]);

        [$sql, $params] = $this->queryBuilder->build($query);

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }
}
