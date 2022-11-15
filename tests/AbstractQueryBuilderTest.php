<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractQueryBuilderTest extends TestCase
{
    use SchemaBuilderTrait;
    use TestTrait;

    protected ConnectionPDOInterface $db;

    public function testAddColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `user` ADD `age` integer
            SQL,
            $qb->addColumn('user', 'age', 'integer')
        );
    }

    public function testBuildColumnsWithString(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $columns = '(id)';

        $this->assertSame($columns, $qb->buildColumns($columns));
    }

    public function testBuildColumnsWithArray(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $columns = ['id', 'name', 'email', 'address', 'status'];
        $expected = DbHelper::replaceQuotes('[[id]], [[name]], [[email]], [[address]], [[status]]', $db->getName());

        $this->assertSame($expected, $qb->buildColumns($columns));
    }

    public function testBuildColumnsWithExpression(): void
    {
        $db = $this->getConnection();

        $columns = ['id', 'name', 'email', 'address', 'status', new Expression('COUNT(*)')];
        $expected = DbHelper::replaceQuotes(
            '[[id]], [[name]], [[email]], [[address]], [[status]], COUNT(*)',
            $db->getName(),
        );
        $qb = $db->getQueryBuilder();

        $this->assertSame($expected, $qb->buildColumns($columns));
    }

    public function testBuildLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->limit(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * LIMIT 10
            SQL,
            $sql,
        );
        $this->assertSame([], $params);
    }

    public function testBuildOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->offset(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * OFFSET 10
            SQL,
            $sql,
        );
        $this->assertSame([], $params);
    }

    public function testBuildSelectColumnWithoutParentheses(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $params = [];
        $sql = $qb->buildSelect(['1'], $params);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[1]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testBuildSelectOptions(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->selectOption('DISTINCT');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT DISTINCT *
            SQL,
            $sql,
        );
        $this->assertSame([], $params);
    }

    /**
     * This test contains three select queries connected with UNION and UNION ALL constructions.
     * It could be useful to use "phpunit --group=db --filter testBuildUnion" command for run it.
     */
    public function testBuildUnion(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            (SELECT [[id]] FROM [[TotalExample]] [[t1]] WHERE (w > 0) AND (x < 2)) UNION ( SELECT [[id]] FROM [[TotalTotalExample]] [[t2]] WHERE w > 5 ) UNION ALL ( SELECT [[id]] FROM [[TotalTotalExample]] [[t3]] WHERE w = 3 )
            SQL,
            $db->getName(),
        );

        $secondQuery = $this->getQuery($db)->select('id')->from('TotalTotalExample t2')->where('w > 5');
        $thirdQuery = $this->getQuery($db)->select('id')->from('TotalTotalExample t3')->where('w = 3');
        $query = $this->getQuery($db)
            ->select('id')
            ->from('TotalExample t1')
            ->where(['and', 'w > 0', 'x < 2'])
            ->union($secondQuery)
            ->union($thirdQuery, true);

        [$actualQuerySql, $queryParams] = $qb->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame([], $queryParams);
    }

    public function testBuildWithQuery(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            WITH a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1), a2 AS ((SELECT [[id]] FROM [[t2]] INNER JOIN [[a1]] ON t2.id = a1.id WHERE expr = 2) UNION ( SELECT [[id]] FROM [[t3]] WHERE expr = 3 )) SELECT * FROM [[a2]]
            SQL,
            $db->getName(),
        );

        $with1Query = $this->getQuery($db)->select('id')->from('t1')->where('expr = 1');
        $with2Query = $this->getQuery($db)->select('id')->from('t2')->innerJoin('a1', 't2.id = a1.id')->where('expr = 2');
        $with3Query = $this->getQuery($db)->select('id')->from('t3')->where('expr = 3');
        $query = $this->getQuery($db)
            ->withQuery($with1Query, 'a1')
            ->withQuery($with2Query->union($with3Query), 'a2')
            ->from('a2');

        [$actualQuerySql, $queryParams] = $qb->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame([], $queryParams);
    }

    public function testBuildWithQueryRecursive(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            WITH RECURSIVE a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1) SELECT * FROM [[a1]]
            SQL,
            $db->getName(),
        );
        $with1Query = $this->getQuery($db)->select('id')->from('t1')->where('expr = 1');
        $query = $this->getQuery($db)->withQuery($with1Query, 'a1', true)->from('a1');

        [$actualQuerySql, $queryParams] = $qb->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame([], $queryParams);
    }

    public function testBuildWhereExistsWithArrayParameters(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (([[w]].[[merchant_id]]=:qp0) AND ([[w]].[[user_id]]=:qp1)))) AND ([[t]].[[some_column]]=:qp2)
            SQL,
            $db->getName(),
        );

        $expectedQueryParams = [':qp0' => 6, ':qp1' => 210, ':qp2' => 'asd'];

        $subQuery = $this->getQuery($db)
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere(['w.merchant_id' => 6, 'w.user_id' => 210]);

        $query = $this->getQuery($db)
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere(['t.some_column' => 'asd']);

        [$actualQuerySql, $queryParams] = $qb->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame($expectedQueryParams, $queryParams);
    }

    public function testBuildWhereExistsWithParameters(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expectedQuerySql = DbHelper::replaceQuotes(
            <<<SQL
            SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (w.merchant_id = :merchant_id))) AND (t.some_column = :some_value)
            SQL,
            $db->getName(),
        );

        $expectedQueryParams = [':some_value' => 'asd', ':merchant_id' => 6];

        $subQuery = $this->getQuery($db)
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere('w.merchant_id = :merchant_id', [':merchant_id' => 6]);

        $query = $this->getQuery($db)
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere('t.some_column = :some_value', [':some_value' => 'asd']);

        [$actualQuerySql, $queryParams] = $qb->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame($expectedQueryParams, $queryParams);
    }

    public function testComplexSelect(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expressionString = DbHelper::replaceQuotes(
            <<<SQL
            case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]]
            SQL,
            $db->getName(),
        );
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT [[t]].[[id]] AS [[ID]], [[gsm]].[[username]] AS [[GSM]], [[part]].[[Part]], [[t]].[[Part_Cost]] AS [[Part Cost]], st_x(location::geometry) AS [[lon]], case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]] FROM [[tablename]]
            SQL,
            $db->getName(),
        );

        $this->assertIsString($expressionString);

        $query = $this->getQuery($db)
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

        [$sql, $params] = $qb->build($query);

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            CREATE VIEW [[test_view]] AS SELECT [[id]], [[name]] FROM [[test_table]]
            SQL,
            $db->getName(),
        );
        $sql = $qb->createView(
            'test_view',
            $this->getQuery($db)->select(['id', 'name'])->from('test_table'),
        );

        $this->assertSame($expected, $sql);
    }

    public function testCreateViewWithParams(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            CREATE VIEW [[test_view]] AS SELECT [[id]], [[name]] FROM [[test_table]] WHERE [[id]]=1
            SQL,
            $db->getName(),
        );
        $sql = $qb->createView(
            'test_view',
            $this->getQuery($db)->select(['id', 'name'])->from('test_table')->where(['id' => 1]),
        );

        $this->assertSame($expected, $sql);
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            COMMENT ON TABLE `test_table` IS NULL
            SQL,
            $db->getName(),
        );

        $sql = $qb->dropCommentFromTable('test_table');

        $this->assertSame($expected, $sql);
    }

    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            DROP TABLE [[test_table]]
            SQL,
            $db->getName(),
        );
        $sql = $qb->dropTable('test_table');

        $this->assertSame($expected, $sql);
    }

    public function testDropView(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            DROP VIEW [[test_view]]
            SQL,
            $db->getName(),
        );
        $sql = $qb->dropView('test_view');

        $this->assertSame($expected, $sql);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/10869}
     */
    public function testFromIndexHint(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->from([new Expression('{{%user}} USE INDEX (primary)')]);

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM {{%user}} USE INDEX (primary)
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = $this->getQuery($db)
            ->from([new Expression('{{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)')])
            ->leftJoin(['p' => 'profile'], 'user.id = profile.user_id USE INDEX (i2)');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM {{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1) LEFT JOIN [[profile]] [[p]] ON user.id = profile.user_id USE INDEX (i2)
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testFromSubquery(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        /* subquery */
        $subquery = $this->getQuery($db)->from('user')->where('account_id = accounts.id');
        $query = $this->getQuery($db)->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = accounts.id) [[activeusers]]
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* subquery with params */
        $subquery = $this->getQuery($db)->from('user')->where('account_id = :id', ['id' => 1]);
        $query = $this->getQuery($db)->from(['activeusers' => $subquery])->where('abc = :abc', ['abc' => 'abc']);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = :id) [[activeusers]] WHERE abc = :abc
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame(['abc' => 'abc', 'id' => 1], $params);

        /* simple subquery */
        $subquery = '(SELECT * FROM user WHERE account_id = accounts.id)';
        $query = $this->getQuery($db)->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM user WHERE account_id = accounts.id) [[activeusers]]
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testGroupBy(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        /* simple string */
        $query = $this->getQuery($db)->select('*')->from('operations')->groupBy('name, date');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = $this->getQuery($db)->select('*')->from('operations')->groupBy(['name', 'date']);

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = $this->getQuery($db)
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->groupBy(new Expression('SUBSTR(name, 0, 1), x'));

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] WHERE account_id = accounts.id GROUP BY SUBSTR(name, 0, 1), x
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = $this->getQuery($db)
            ->select('*')
            ->from('operations')
            ->groupBy(new Expression('SUBSTR(name, 0, :to), x', [':to' => 4]));

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY SUBSTR(name, 0, :to), x
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame([':to' => 4], $params);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15653}
     */
    public function testIssue15653(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->from('admin_user')->where(['is_deleted' => false]);
        $query->where([])->andWhere(['in', 'id', ['1', '0']]);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[admin_user]] WHERE [[id]] IN (:qp0, :qp1)
                SQL,
                $db->getName(),
            ),
            $sql,
        );
        $this->assertSame([':qp0' => '1', ':qp1' => '0'], $params);
    }

    public function testOrderBy(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        /* simple string */
        $query = $this->getQuery($db)->select('*')->from('operations')->orderBy('name ASC, date DESC');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = $this->getQuery($db)->select('*')->from('operations')->orderBy(['name' => SORT_ASC, 'date' => SORT_DESC]);

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = $this->getQuery($db)
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->orderBy(new Expression('SUBSTR(name, 3, 4) DESC, x ASC'));

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] WHERE account_id = accounts.id ORDER BY SUBSTR(name, 3, 4) DESC, x ASC
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = $this->getQuery($db)
            ->select('*')
            ->from('operations')
            ->orderBy(new Expression('SUBSTR(name, 3, :to) DESC, x ASC', [':to' => 4]));

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY SUBSTR(name, 3, :to) DESC, x ASC
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame([':to' => 4], $params);
    }

    public function testRenameColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->renameColumn('alpha', 'string_identifier', 'string_identifier_test');

        $this->assertSame(
            <<<SQL
            ALTER TABLE `alpha` RENAME COLUMN `string_identifier` TO `string_identifier_test`
            SQL,
            $sql,
        );

        $sql = $qb->renameColumn('alpha', 'string_identifier_test', 'string_identifier');

        $this->assertSame(
            <<<SQL
            ALTER TABLE `alpha` RENAME COLUMN `string_identifier_test` TO `string_identifier`
            SQL,
            $sql,
        );
    }

    public function testSelectExpression(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->select(new Expression('1 AS ab'))->from('tablename');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT 1 AS ab FROM [[tablename]]
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = $this->getQuery($db)
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = $this->getQuery($db)
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT SUBSTR(name, 0, :len) FROM [[tablename]]
            SQL,
            $db->getName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame([':len' => 4], $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::selectExist()
     */
    public function testSelectExists(string $sql, string $expected): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sqlSelectExist = $qb->selectExists($sql);

        $this->assertSame($expected, $sqlSelectExist);
    }

    public function testSelectSubquery(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT *, (SELECT COUNT(*) FROM [[operations]] WHERE account_id = accounts.id) AS [[operations_count]] FROM [[accounts]]
            SQL,
            $db->getName(),
        );
        $subquery = $this->getQuery($db)->select('COUNT(*)')->from('operations')->where('account_id = accounts.id');
        $query = $this->getQuery($db)->select('*')->from('accounts')->addSelect(['operations_count' => $subquery]);

        [$sql, $params] = $qb->build($query);

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testSetConditionClasses(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $qb->setConditionClasses(['stdClass' => stdClass::class]);
        $dqlBuilder = Assert::getInaccessibleProperty($qb, 'dqlBuilder');
        $conditionClasses = Assert::getInaccessibleProperty($dqlBuilder, 'conditionClasses');

        $this->assertSame(stdClass::class, $conditionClasses['stdClass']);
    }

    public function testSelectExpressionBuilder(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $qb->setExpressionBuilders(['stdClass' => stdClass::class]);
        $dqlBuilder = Assert::getInaccessibleProperty($qb, 'dqlBuilder');
        $expressionBuilders = Assert::getInaccessibleProperty($dqlBuilder, 'expressionBuilders');

        $this->assertSame(stdClass::class, $expressionBuilders['stdClass']);
    }

    public function testSetSeparator(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $qb->setSeparator(' ');
        [$sql, $params] = $qb->build($this->getQuery($db)->select('*')->from('table'));

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[table]]
                SQL,
                $db->getName(),
            ),
            $sql
        );
        $this->assertEmpty($params);

        $qb->setSeparator("\n");
        [$sql, $params] = $qb->build($this->getQuery($db)->select('*')->from('table'));

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT *
                FROM [[table]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);
    }

    public function testTruncateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->truncateTable('table');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                TRUNCATE TABLE [[table]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );

        $sql = $qb->truncateTable('table2');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                TRUNCATE TABLE [[table2]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }
}
