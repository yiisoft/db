<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

trait TestQueryBuilderTrait
{
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
     * This test contains three select queries connected with UNION and UNION ALL constructions.
     * It could be useful to use "phpunit --group=db --filter testBuildUnion" command for run it.
     */
    public function testBuildUnion(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            '(SELECT [[id]] FROM [[TotalExample]] [[t1]] WHERE (w > 0) AND (x < 2)) UNION ( SELECT [[id]]'
            . ' FROM [[TotalTotalExample]] [[t2]] WHERE w > 5 ) UNION ALL ( SELECT [[id]] FROM [[TotalTotalExample]]'
            . ' [[t3]] WHERE w = 3 )'
        );

        $query = new Query($db);
        $secondQuery = new Query($db);

        $secondQuery->select('id')
              ->from('TotalTotalExample t2')
              ->where('w > 5');

        $thirdQuery = new Query($db);

        $thirdQuery->select('id')
              ->from('TotalTotalExample t3')
              ->where('w = 3');

        $query->select('id')
              ->from('TotalExample t1')
              ->where(['and', 'w > 0', 'x < 2'])
              ->union($secondQuery)
              ->union($thirdQuery, true);

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }

    public function testBuildWithQuery(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'WITH a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1), a2 AS ((SELECT [[id]] FROM [[t2]]'
            . ' INNER JOIN [[a1]] ON t2.id = a1.id WHERE expr = 2) UNION ( SELECT [[id]] FROM [[t3]] WHERE expr = 3 ))'
            . ' SELECT * FROM [[a2]]'
        );

        $with1Query = (new Query($db))
            ->select('id')
            ->from('t1')
            ->where('expr = 1');

        $with2Query = (new Query($db))
            ->select('id')
            ->from('t2')
            ->innerJoin('a1', 't2.id = a1.id')
            ->where('expr = 2');

        $with3Query = (new Query($db))
            ->select('id')
            ->from('t3')
            ->where('expr = 3');

        $query = (new Query($db))
            ->withQuery($with1Query, 'a1')
            ->withQuery($with2Query->union($with3Query), 'a2')
            ->from('a2');

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }

    public function testBuildWithQueryRecursive(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'WITH RECURSIVE a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1) SELECT * FROM [[a1]]'
        );

        $with1Query = (new Query($db))
            ->select('id')
            ->from('t1')
            ->where('expr = 1');

        $query = (new Query($db))
            ->withQuery($with1Query, 'a1', true)
            ->from('a1');

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }

    public function testBuildWhereExistsWithArrayParameters(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]]'
            . ' WHERE (w.id = t.website_id) AND (([[w]].[[merchant_id]]=:qp0) AND ([[w]].[[user_id]]=:qp1))))'
            . ' AND ([[t]].[[some_column]]=:qp2)'
        );

        $expectedQueryParams = [':qp0' => 6, ':qp1' => 210, ':qp2' => 'asd'];

        $subQuery = new Query($db);

        $subQuery->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere(['w.merchant_id' => 6, 'w.user_id' => '210']);

        $query = new Query($db);

        $query->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere(['t.some_column' => 'asd']);

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $queryParams);
    }

    public function testBuildWhereExistsWithParameters(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]]'
            . ' WHERE (w.id = t.website_id) AND (w.merchant_id = :merchant_id))) AND (t.some_column = :some_value)'
        );

        $expectedQueryParams = [':some_value' => 'asd', ':merchant_id' => 6];

        $subQuery = new Query($db);

        $subQuery->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere('w.merchant_id = :merchant_id', [':merchant_id' => 6]);

        $query = new Query($db);

        $query->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere('t.some_column = :some_value', [':some_value' => 'asd']);

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $queryParams);
    }

    public function testComplexSelect(): void
    {
        $db = $this->getConnection();

        $expressionString = $this->replaceQuotes(
            "case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action'"
            . ' END as [[Next Action]]'
        );
        $this->assertIsString($expressionString);

        $query = (new Query($db))
            ->select([
                'ID' => 't.id',
                'gsm.username as GSM',
                'part.Part',
                'Part Cost' => 't.Part_Cost',
                'st_x(location::geometry) as lon',
                new Expression($expressionString),
            ])
            ->from('tablename');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT [[t]].[[id]] AS [[ID]], [[gsm]].[[username]] AS [[GSM]], [[part]].[[Part]], [[t]].[[Part_Cost]]'
            . ' AS [[Part Cost]], st_x(location::geometry) AS [[lon]], case t.Status_Id when 1 then \'Acknowledge\''
            . ' when 2 then \'No Action\' else \'Unknown Action\' END as [[Next Action]] FROM [[tablename]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/10869}
     */
    public function testFromIndexHint(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from([new Expression('{{%user}} USE INDEX (primary)')]);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM {{%user}} USE INDEX (primary)');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->from([new Expression('{{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)')])
            ->leftJoin(['p' => 'profile'], 'user.id = profile.user_id USE INDEX (i2)');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM {{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)'
            . ' LEFT JOIN [[profile]] [[p]] ON user.id = profile.user_id USE INDEX (i2)'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testFromSubquery(): void
    {
        $db = $this->getConnection();

        /* query subquery */
        $subquery = (new Query($db))->from('user')->where('account_id = accounts.id');

        $query = (new Query($db))->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = accounts.id) [[activeusers]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* query subquery with params */
        $subquery = (new Query($db))->from('user')->where('account_id = :id', ['id' => 1]);

        $query = (new Query($db))->from(['activeusers' => $subquery])->where('abc = :abc', ['abc' => 'abc']);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = :id) [[activeusers]] WHERE abc = :abc'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEquals(['id' => 1, 'abc' => 'abc'], $params);

        /* simple subquery */
        $subquery = '(SELECT * FROM user WHERE account_id = accounts.id)';

        $query = (new Query($db))->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM user WHERE account_id = accounts.id) [[activeusers]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testGroupBy(): void
    {
        $db = $this->getConnection();

        /* simple string */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy('name, date');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy(['name', 'date']);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->groupBy(new Expression('SUBSTR(name, 0, 1), x'));

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM [[operations]] WHERE account_id = accounts.id GROUP BY SUBSTR(name, 0, 1), x'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy(new Expression('SUBSTR(name, 0, :to), x', [':to' => 4]));

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY SUBSTR(name, 0, :to), x');

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':to' => 4], $params);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15653}
     */
    public function testIssue15653(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))
            ->from('admin_user')
            ->where(['is_deleted' => false]);

        $query
            ->where([])
            ->andWhere(['in', 'id', ['1', '0']]);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $this->assertSame($this->replaceQuotes('SELECT * FROM [[admin_user]] WHERE [[id]] IN (:qp0, :qp1)'), $sql);
        $this->assertSame([':qp0' => '1', ':qp1' => '0'], $params);
    }

    public function testOrderBy(): void
    {
        $db = $this->getConnection();

        /* simple string */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy('name ASC, date DESC');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy(['name' => SORT_ASC, 'date' => SORT_DESC]);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->orderBy(new Expression('SUBSTR(name, 3, 4) DESC, x ASC'));

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM [[operations]] WHERE account_id = accounts.id ORDER BY SUBSTR(name, 3, 4) DESC, x ASC'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy(new Expression('SUBSTR(name, 3, :to) DESC, x ASC', [':to' => 4]));

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY SUBSTR(name, 3, :to) DESC, x ASC');

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':to' => 4], $params);
    }

    public function testSelectExpression(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))
            ->select(new Expression('1 AS ab'))
            ->from('tablename');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT 1 AS ab FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT SUBSTR(name, 0, :len) FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':len' => 4], $params);
    }

    public function testSelectSubquery(): void
    {
        $db = $this->getConnection();

        $subquery = (new Query($db))
            ->select('COUNT(*)')
            ->from('operations')
            ->where('account_id = accounts.id');

        $query = (new Query($db))
            ->select('*')
            ->from('accounts')
            ->addSelect(['operations_count' => $subquery]);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT *, (SELECT COUNT(*) FROM [[operations]] WHERE account_id = accounts.id) AS [[operations_count]]'
            . ' FROM [[accounts]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }
}
