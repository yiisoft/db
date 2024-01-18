<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Closure;
use JsonException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;
use Yiisoft\Db\Command\DataType;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\Condition\SimpleCondition;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class AbstractQueryBuilderTest extends TestCase
{
    use TestTrait;

    public function testAddCheck(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addCheck('CN_check', 'T_constraints_1', '[[C_not_null]] > 100');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[CN_check]] ADD CONSTRAINT [[T_constraints_1]] CHECK ([[C_not_null]] > 100)
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testAddColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addColumn('table', 'column', SchemaInterface::TYPE_STRING);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD [[column]]
                SQL . ' ' . $qb->getColumnType(SchemaInterface::TYPE_STRING),
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    /**
     * @throws Exception
     */
    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnColumn('customer', 'id', 'Primary key.');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON COLUMN [[customer]].[[id]] IS 'Primary key.'
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    /**
     * @throws Exception
     */
    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnTable('customer', 'Customer table.');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON TABLE [[customer]] IS 'Customer table.'
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addDefaultValue('T_constraints_1', 'CN_pk', 'C_default', 1);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[T_constraints_1]] ALTER COLUMN [[C_default]] SET DEFAULT 1
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::addForeignKey
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testAddForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string|null $delete,
        string|null $update,
        string $expected
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addForeignKey($table, $name, $columns, $refTable, $refColumns, $delete, $update);

        $this->assertSame($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::addPrimaryKey
     */
    public function testAddPrimaryKey(string $name, string $table, array|string $columns, string $expected): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addPrimaryKey($table, $name, $columns);

        $this->assertSame($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::addUnique
     */
    public function testAddUnique(string $name, string $table, array|string $columns, string $expected): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->addUnique($table, $name, $columns);

        $this->assertSame($expected, $sql);
    }

    public function testAlterColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->alterColumn('customer', 'email', SchemaInterface::TYPE_STRING);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[customer]] CHANGE [[email]] [[email]]
                SQL . ' ' . $qb->getColumnType(SchemaInterface::TYPE_STRING),
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::batchInsert
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @psalm-param array<array-key, string> $columns
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        iterable $rows,
        string $expected,
        array $expectedParams = [],
    ): void {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $params = [];
        $sql = $qb->batchInsert($table, $columns, $rows, $params);

        $this->assertSame($expected, $sql);
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildCondition
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $this->assertEquals(
            'SELECT *'
            . ($db->getDriverName() === 'oci' ? ' FROM DUAL' : '')
            . (empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes($expected, $db->getDriverName())),
            $sql
        );
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testBuildColumnsWithString(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame('(id)', $qb->buildColumns('(id)'));
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testBuildColumnsWithArray(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes('[[id]], [[name]], [[email]], [[address]], [[status]]', $db->getDriverName()),
            $qb->buildColumns(['id', 'name', 'email', 'address', 'status']),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testBuildColumnsWithExpression(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                '[[id]], [[name]], [[email]], [[address]], [[status]], COUNT(*)',
                $db->getDriverName(),
            ),
            $qb->buildColumns(['id', 'name', 'email', 'address', 'status', new Expression('COUNT(*)')]),
        );
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15653}
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildIssue15653(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user')->where(['is_deleted' => false]);
        $query->where([])->andWhere(['in', 'id', ['1', '0']]);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[admin_user]] WHERE [[id]] IN (:qp0, :qp1)
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([':qp0' => '1', ':qp1' => '0'], $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildFilterCondition
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->filterWhere($condition);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            'SELECT *'
            . ($db->getDriverName() === 'oci' ? ' FROM DUAL' : '')
            . (empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes($expected, $db->getDriverName())),
            $sql,
        );
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildFrom(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user');
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                FROM [[admin_user]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildFrom($query->getFrom(), $params),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testBuildGroupBy(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user')->groupBy(['id', 'name']);
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                GROUP BY [[id]], [[name]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildGroupBy($query->getGroupBy(), $params),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildHaving(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user')->having(['id' => 1]);
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                HAVING [[id]]=:qp0
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildHaving($query->getHaving(), $params),
        );
    }

    /**
     * @throws Exception
     */
    public function testBuildJoin(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))
            ->from('admin_user')
            ->join('INNER JOIN', 'admin_profile', 'admin_user.id = admin_profile.user_id');
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                INNER JOIN [[admin_profile]] ON admin_user.id = admin_profile.user_id
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildJoin($query->getJoins(), $params),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildLikeCondition
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $this->assertSame(
            'SELECT *'
            . ($db->getDriverName() === 'oci' ? ' FROM DUAL' : '')
            . (empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes($expected, $db->getDriverName())),
            $sql
        );
        $this->assertSame($expectedParams, $params);
    }

    public function testBuildLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user')->limit(10);

        $this->assertSame('LIMIT 10', $qb->buildLimit($query->getLimit(), 0));
    }

    public function testBuildLimitOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user')->limit(10)->offset(5);

        $this->assertSame('LIMIT 10 OFFSET 5', $qb->buildLimit($query->getLimit(), $query->getOffset()));
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testBuildOrderBy(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user')->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC]);
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ORDER BY [[id]], [[name]] DESC
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildOrderBy($query->getOrderBy(), $params),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testBuildOrderByAndLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))
            ->from('admin_user')
            ->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC])
            ->limit(10)
            ->offset(5);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[admin_user]] ORDER BY [[id]], [[name]] DESC LIMIT 10 OFFSET 5
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildOrderByAndLimit(
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[admin_user]]
                    SQL,
                    $db->getDriverName(),
                ),
                $query->getOrderBy(),
                $query->getLimit(),
                $query->getOffset(),
            ),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildSelect(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->select(['id', 'name', 'email', 'address', 'status']);
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[id]], [[name]], [[email]], [[address]], [[status]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildSelect($query->getSelect(), $params),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildSelectWithAlias(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[id]] AS [[a]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildSelect(['id as a'], $params),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildSelectWithDistinct(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->select(['id', 'name', 'email', 'address', 'status'])->distinct();
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT DISTINCT [[id]], [[name]], [[email]], [[address]], [[status]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildSelect($query->getSelect(), $params, true),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildUnion(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('admin_user')->union((new Query($db))->from('admin_profile'));
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                UNION ( SELECT * FROM [[admin_profile]] )
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildUnion($query->getUnions(), $params),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithQueries(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->withQuery((new Query($db))->from('admin_user')->from('admin_profile'), 'cte');
        $params = [];

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                WITH [[cte]] AS (SELECT * FROM [[admin_profile]])
                SQL,
                $db->getDriverName(),
            ),
            $qb->buildWithQueries($query->getWithQueries(), $params),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithComplexSelect(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expressionString = DbHelper::replaceQuotes(
            <<<SQL
            case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]]
            SQL,
            $db->getDriverName(),
        );

        $this->assertIsString($expressionString);

        $query = (new Query($db))
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

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[t]].[[id]] AS [[ID]], [[gsm]].[[username]] AS [[GSM]], [[part]].[[Part]], [[t]].[[Part_Cost]] AS [[Part Cost]], st_x(location::geometry) AS [[lon]], case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]] FROM [[tablename]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildFrom
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithFrom(
        ExpressionInterface|array|string $table,
        string $expectedSql,
        array $expectedParams = []
    ): void {
        $db = $this->getConnection();

        $query = (new Query($db))->from($table);
        $queryBuilder = $db->getQueryBuilder();

        [$sql, $params] = $queryBuilder->build($query);

        $this->assertSame($expectedSql, $sql);
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithFromAliasesNoExist(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('no_exist_table');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[no_exist_table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );

        $this->assertSame([], $params);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/10869}
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithFromIndexHint(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from([new Expression('{{%user}} USE INDEX (primary)')]);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM {{%user}} USE INDEX (primary)
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );

        $this->assertEmpty($params);

        $query = (new Query($db))
            ->from([new Expression('{{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)')])
            ->leftJoin(['p' => 'profile'], 'user.id = profile.user_id USE INDEX (i2)');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM {{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1) LEFT JOIN [[profile]] [[p]] ON user.id = profile.user_id USE INDEX (i2)
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );

        $this->assertEmpty($params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithFromSubquery(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        /* subquery */
        $subquery = (new Query($db))->from('user')->where('account_id = accounts.id');
        $query = (new Query($db))->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = accounts.id) [[activeusers]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        /* subquery with params */
        $subquery = (new Query($db))->from('user')->where('account_id = :id', ['id' => 1]);
        $query = (new Query($db))->from(['activeusers' => $subquery])->where('abc = :abc', ['abc' => 'abc']);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = :id) [[activeusers]] WHERE abc = :abc
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame(['abc' => 'abc', 'id' => 1], $params);

        /* simple subquery */
        $subquery = '(SELECT * FROM user WHERE account_id = accounts.id)';
        $query = (new Query($db))->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM (SELECT * FROM user WHERE account_id = accounts.id) [[activeusers]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithGroupBy(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        /* simple string */
        $query = (new Query($db))->select('*')->from('operations')->groupBy('name, date');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))->select('*')->from('operations')->groupBy(['name', 'date']);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->groupBy(new Expression('SUBSTR(name, 0, 1), x'));

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] WHERE account_id = accounts.id GROUP BY SUBSTR(name, 0, 1), x
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        /* expression with params */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy(new Expression('SUBSTR(name, 0, :to), x', [':to' => 4]));

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] GROUP BY SUBSTR(name, 0, :to), x
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([':to' => 4], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->limit(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * LIMIT 10
            SQL,
            $sql,
        );

        $this->assertSame([], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->offset(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * OFFSET 10
            SQL,
            $sql,
        );
        $this->assertSame([], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithOrderBy(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        /* simple string */
        $query = (new Query($db))->select('*')->from('operations')->orderBy('name ASC, date DESC');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))->select('*')->from('operations')->orderBy(['name' => SORT_ASC, 'date' => SORT_DESC]);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->orderBy(new Expression('SUBSTR(name, 3, 4) DESC, x ASC'));

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] WHERE account_id = accounts.id ORDER BY SUBSTR(name, 3, 4) DESC, x ASC
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        /* expression with params */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy(new Expression('SUBSTR(name, 3, :to) DESC, x ASC', [':to' => 4]));

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[operations]] ORDER BY SUBSTR(name, 3, :to) DESC, x ASC
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([':to' => 4], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithQuery(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $with1Query = (new query($db))->select('id')->from('t1')->where('expr = 1');
        $with2Query = (new query($db))->select('id')->from('t2')->innerJoin('a1', 't2.id = a1.id')->where('expr = 2');
        $with3Query = (new query($db))->select('id')->from('t3')->where('expr = 3');
        $query = (new query($db))
            ->withQuery($with1Query, 'a1')
            ->withQuery($with2Query->union($with3Query), 'a2')
            ->from('a2');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                WITH [[a1]] AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1), [[a2]] AS ((SELECT [[id]] FROM [[t2]] INNER JOIN [[a1]] ON t2.id = a1.id WHERE expr = 2) UNION ( SELECT [[id]] FROM [[t3]] WHERE expr = 3 )) SELECT * FROM [[a2]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );

        $this->assertSame([], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithQueryRecursive(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $with1Query = (new Query($db))->select('id')->from('t1')->where('expr = 1');
        $query = (new Query($db))->withQuery($with1Query, 'a1', true)->from('a1');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            WITH RECURSIVE [[a1]] AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1) SELECT * FROM [[a1]]
            SQL,
            $db->getDriverName(),
        );

        if (in_array($db->getDriverName(), ['oci', 'sqlsrv'], true)) {
            $expected = str_replace('WITH RECURSIVE ', 'WITH ', $expected);
        }

        $this->assertSame($expected, $sql);
        $this->assertSame([], $params);
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::cteAliases */
    public function testBuildWithQueryAlias($alias, $expected)
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $withQuery = (new Query($db))->from('t');
        $query = (new Query($db))->withQuery($withQuery, $alias)->from('t');

        [$sql, $params] = $qb->build($query);

        $expectedSql = DbHelper::replaceQuotes(
            <<<SQL
            WITH $expected AS (SELECT * FROM [[t]]) SELECT * FROM [[t]]
            SQL,
            $db->getDriverName(),
        );

        $this->assertSame($expectedSql, $sql);
        $this->assertSame([], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithSelectExpression(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->select(new Expression('1 AS ab'))->from('tablename');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT 1 AS ab FROM [[tablename]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT SUBSTR(name, 0, :len) FROM [[tablename]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([':len' => 4], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithSelectSubquery(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $subquery = (new Query($db))->select('COUNT(*)')->from('operations')->where('account_id = accounts.id');
        $query = (new Query($db))->select('*')->from('accounts')->addSelect(['operations_count' => $subquery]);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT *, (SELECT COUNT(*) FROM [[operations]] WHERE account_id = accounts.id) AS [[operations_count]] FROM [[accounts]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithSelectOption(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->selectOption('DISTINCT');

        [$sql, $params] = $qb->build($query);

        $expected = 'SELECT DISTINCT *';

        if ($this->getDriverName() === 'oci') {
            $expected .= ' FROM DUAL';
        }

        $this->assertSame($expected, $sql);
        $this->assertSame([], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithSetSeparator(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $qb->setSeparator(' ');

        [$sql, $params] = $qb->build((new Query($db))->select('*')->from('table'));

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql
        );
        $this->assertEmpty($params);

        $qb->setSeparator("\n");
        [$sql, $params] = $qb->build((new Query($db))->select('*')->from('table'));

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT *
                FROM [[table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithUnion(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $secondQuery = (new Query($db))->select('id')->from('TotalTotalExample t2')->where('w > 5');
        $thirdQuery = (new Query($db))->select('id')->from('TotalTotalExample t3')->where('w = 3');
        $firtsQuery = (new Query($db))
            ->select('id')
            ->from('TotalExample t1')
            ->where(['and', 'w > 0', 'x < 2'])
            ->union($secondQuery)
            ->union($thirdQuery, true);

        [$sql, $params] = $qb->build($firtsQuery);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                (SELECT [[id]] FROM [[TotalExample]] [[t1]] WHERE (w > 0) AND (x < 2)) UNION ( SELECT [[id]] FROM [[TotalTotalExample]] [[t2]] WHERE w > 5 ) UNION ALL ( SELECT [[id]] FROM [[TotalTotalExample]] [[t3]] WHERE w = 3 )
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([], $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildWhereExists
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection();

        $expectedQueryParams = [];

        $subQuery = new Query($db);
        $subQuery->select('1')->from('Website w');
        $query = new Query($db);
        $query->select('id')->from('TotalExample t')->where([$cond, $subQuery]);

        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame($expectedQueryParams, $actualQueryParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithWhereExistsArrayParameters(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $subQuery = (new query($db))
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere(['w.merchant_id' => 6, 'w.user_id' => 210]);
        $query = (new query($db))
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere(['t.some_column' => 'asd']);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (([[w]].[[merchant_id]]=:qp0) AND ([[w]].[[user_id]]=:qp1)))) AND ([[t]].[[some_column]]=:qp2)
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([':qp0' => 6, ':qp1' => 210, ':qp2' => 'asd'], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithWhereExistsWithParameters(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $subQuery = (new query($db))
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere('w.merchant_id = :merchant_id', [':merchant_id' => 6]);
        $query = (new query($db))
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere('t.some_column = :some_value', [':some_value' => 'asd']);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (w.merchant_id = :merchant_id))) AND (t.some_column = :some_value)
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertSame([':some_value' => 'asd', ':merchant_id' => 6], $params);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testsCreateConditionFromArray(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $condition = $qb->createConditionFromArray(['and', 'a = 1', 'b = 2']);

        $this->assertSame('AND', $condition->getOperator());
        $this->assertSame(['a = 1', 'b = 2'], $condition->getExpressions());

        $condition = $qb->createConditionFromArray(['or', 'a = 1', 'b = 2']);

        $this->assertSame('OR', $condition->getOperator());
        $this->assertSame(['a = 1', 'b = 2'], $condition->getExpressions());

        $condition = $qb->createConditionFromArray(['and', 'a = 1', ['or', 'b = 2', 'c = 3']]);

        $this->assertSame('AND', $condition->getOperator());
        $this->assertSame(['a = 1', ['or', 'b = 2', 'c = 3']], $condition->getExpressions());

        $condition = $qb->createConditionFromArray(['or', 'a = 1', ['and', 'b = 2', 'c = 3']]);

        $this->assertSame('OR', $condition->getOperator());
        $this->assertSame(['a = 1', ['and', 'b = 2', 'c = 3']], $condition->getExpressions());

        $condition = $qb->createConditionFromArray(['and', 'a = 1', ['or', 'b = 2', ['and', 'c = 3', 'd = 4']]]);

        $this->assertSame('AND', $condition->getOperator());
        $this->assertSame(['a = 1', ['or', 'b = 2', ['and', 'c = 3', 'd = 4']]], $condition->getExpressions());

        $condition = $qb->createConditionFromArray(['or', 'a = 1', ['and', 'b = 2', ['or', 'c = 3', 'd = 4']]]);

        $this->assertSame('OR', $condition->getOperator());
        $this->assertSame(['a = 1', ['and', 'b = 2', ['or', 'c = 3', 'd = 4']]], $condition->getExpressions());

        $condition = $qb->createConditionFromArray(
            ['and', 'a = 1', ['or', 'b = 2', ['and', 'c = 3', ['or', 'd = 4', 'e = 5']]]]
        );
        $this->assertSame('AND', $condition->getOperator());
        $this->assertSame(
            ['a = 1', ['or', 'b = 2', ['and', 'c = 3', ['or', 'd = 4', 'e = 5']]]],
            $condition->getExpressions(),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::createIndex
     */
    public function testCreateIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($qb));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testCreateView(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = 'CREATE VIEW [[animal_view]] AS SELECT [[1]]';

        if ($this->getDriverName() === 'oci') {
            $expected .= ' FROM DUAL';
        }

        $this->assertSame(
            DbHelper::replaceQuotes($expected, $db->getDriverName()),
            $qb->createView('animal_view', (new query($db))->select('1')),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::delete
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $actualParams = [];
        $actualSQL = $qb->delete($table, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function testDropCheck(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[T_constraints_1]] DROP CONSTRAINT [[CN_check]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropCheck('T_constraints_1', 'CN_check'),
        );
    }

    public function testDropColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[customer]] DROP COLUMN [[id]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropColumn('customer', 'id'),
        );
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON COLUMN [customer].[id] IS NULL
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropCommentFromColumn('customer', 'id'),
        );
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON TABLE [[customer]] IS NULL
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropCommentFromTable('customer'),
        );
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[T_constraints_1]] ALTER COLUMN [[C_default]] DROP DEFAULT
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropDefaultValue('T_constraints_1', 'CN_pk'),
        );
    }

    public function testDropForeignKey(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[T_constraints_3]] DROP CONSTRAINT [[CN_constraints_3]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropForeignKey('T_constraints_3', 'CN_constraints_3'),
        );
    }

    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DROP INDEX [[CN_constraints_2_single]] ON [[T_constraints_2]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropIndex('T_constraints_2', 'CN_constraints_2_single'),
        );
    }

    public function testDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[T_constraints_1]] DROP CONSTRAINT [[CN_pk]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropPrimaryKey('T_constraints_1', 'CN_pk'),
        );
    }

    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DROP TABLE [[customer]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropTable('customer'),
        );
    }

    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[test_uq]] DROP CONSTRAINT [[test_uq_constraint]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropUnique('test_uq', 'test_uq_constraint'),
        );
    }

    public function testDropView(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DROP VIEW [[animal_view]]
                SQL,
                $db->getDriverName(),
            ),
            $qb->dropview('animal_view'),
        );
    }

    public function testGetColumnType(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame('pk', $qb->getColumnType(SchemaInterface::TYPE_PK));
        $this->assertSame('upk', $qb->getColumnType(SchemaInterface::TYPE_UPK));
        $this->assertSame('bigpk', $qb->getColumnType(SchemaInterface::TYPE_BIGPK));
        $this->assertSame('ubigpk', $qb->getColumnType(SchemaInterface::TYPE_UBIGPK));
        $this->assertSame('char', $qb->getColumnType(SchemaInterface::TYPE_CHAR));
        $this->assertSame('string', $qb->getColumnType(SchemaInterface::TYPE_STRING));
        $this->assertSame('text', $qb->getColumnType(SchemaInterface::TYPE_TEXT));
        $this->assertSame('tinyint', $qb->getColumnType(SchemaInterface::TYPE_TINYINT));
        $this->assertSame('smallint', $qb->getColumnType(SchemaInterface::TYPE_SMALLINT));
        $this->assertSame('integer', $qb->getColumnType(SchemaInterface::TYPE_INTEGER));
        $this->assertSame('bigint', $qb->getColumnType(SchemaInterface::TYPE_BIGINT));
        $this->assertSame('float', $qb->getColumnType(SchemaInterface::TYPE_FLOAT));
        $this->assertSame('double', $qb->getColumnType(SchemaInterface::TYPE_DOUBLE));
        $this->assertSame('decimal', $qb->getColumnType(SchemaInterface::TYPE_DECIMAL));
        $this->assertSame('datetime', $qb->getColumnType(SchemaInterface::TYPE_DATETIME));
        $this->assertSame('timestamp', $qb->getColumnType(SchemaInterface::TYPE_TIMESTAMP));
        $this->assertSame('time', $qb->getColumnType(SchemaInterface::TYPE_TIME));
        $this->assertSame('date', $qb->getColumnType(SchemaInterface::TYPE_DATE));
        $this->assertSame('binary', $qb->getColumnType(SchemaInterface::TYPE_BINARY));
        $this->assertSame('boolean', $qb->getColumnType(SchemaInterface::TYPE_BOOLEAN));
        $this->assertSame('money', $qb->getColumnType(SchemaInterface::TYPE_MONEY));
        $this->assertSame('json', $qb->getColumnType(SchemaInterface::TYPE_JSON));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetExpressionBuilder(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $simpleCondition = new SimpleCondition('a', '=', 1);

        $this->assertInstanceOf(
            ExpressionBuilderInterface::class,
            $qb->getExpressionBuilder($simpleCondition),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::insert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->insert($table, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->insertWithReturningPks($table, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }

    public function testQuoter(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertInstanceOf(QuoterInterface::class, $qb->quoter());
    }

    public function testRenameColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->renameColumn('alpha', 'string_identifier', 'string_identifier_test');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[alpha]] RENAME COLUMN [[string_identifier]] TO [[string_identifier_test]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->renameTable('alpha', 'alpha-test');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                RENAME TABLE [[alpha]] TO [[alpha-test]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            SELECT SETVAL('"item_id_seq"',(SELECT COALESCE(MAX("id"),0) FROM "item")+1,false)
            SQL,
            $qb->resetSequence('item'),
        );

        $this->assertSame(
            <<<SQL
            SELECT SETVAL('"item_id_seq"',3,false)
            SQL,
            $qb->resetSequence('item', 3),
        );
    }

    /**
     * @throws Exception
     */
    public function testResetSequenceNoAssociatedException(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        if ($db->getDriverName() === 'db') {
            $this->expectException(NotSupportedException::class);
            $this->expectExceptionMessage(
                'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::resetSequence() is not supported by this DBMS.'
            );
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(
                "There is not sequence associated with table 'type'."
            );
        }

        $qb->resetSequence('type');
    }

    /**
     * @throws Exception
     */
    public function testResetSequenceTableNoExistException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        if ($db->getDriverName() === 'db') {
            $this->expectException(NotSupportedException::class);
            $this->expectExceptionMessage(
                'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::resetSequence() is not supported by this DBMS.'
            );
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage("Table not found: 'noExist'.");
        }

        $qb->resetSequence('noExist', 1);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::selectExist
     */
    public function testSelectExists(string $sql, string $expected): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sqlSelectExist = $qb->selectExists($sql);

        $this->assertSame($expected, $sqlSelectExist);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testSelectExpression(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->select(new Expression('1 AS ab'))->from('tablename');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT 1 AS ab FROM [[tablename]]
            SQL,
            $db->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]
            SQL,
            $db->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $qb->build($query);

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT SUBSTR(name, 0, :len) FROM [[tablename]]
            SQL,
            $db->getDriverName(),
        );

        $this->assertSame($expected, $sql);
        $this->assertSame([':len' => 4], $params);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testSelectSubquery(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expected = DbHelper::replaceQuotes(
            <<<SQL
            SELECT *, (SELECT COUNT(*) FROM [[operations]] WHERE account_id = accounts.id) AS [[operations_count]] FROM [[accounts]]
            SQL,
            $db->getDriverName(),
        );
        $subquery = (new Query($db))->select('COUNT(*)')->from('operations')->where('account_id = accounts.id');
        $query = (new Query($db))->select('*')->from('accounts')->addSelect(['operations_count' => $subquery]);

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

    public function testSetExpressionBuilder(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $qb->setExpressionBuilders(['stdClass' => stdClass::class]);
        $dqlBuilder = Assert::getInaccessibleProperty($qb, 'dqlBuilder');
        $expressionBuilders = Assert::getInaccessibleProperty($dqlBuilder, 'expressionBuilders');

        $this->assertSame(stdClass::class, $expressionBuilders['stdClass']);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testSetSeparator(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $qb->setSeparator(' ');
        [$sql, $params] = $qb->build((new Query($db))->select('*')->from('table'));

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT * FROM [[table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql
        );
        $this->assertEmpty($params);

        $qb->setSeparator("\n");
        [$sql, $params] = $qb->build((new Query($db))->select('*')->from('table'));

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT *
                FROM [[table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
        $this->assertEmpty($params);
    }

    public function testTruncateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->truncateTable('customer');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                TRUNCATE TABLE [[customer]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );

        $sql = $qb->truncateTable('T_constraints_1');

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                TRUNCATE TABLE [[T_constraints_1]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::update
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $actualParams = [];

        $this->assertSame($expectedSQL, $qb->update($table, $columns, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::upsert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $actualParams = [];
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);

        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::upsert
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpsertExecute(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        $db = $this->getConnection(true);

        $actualParams = [];
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);

        $countQuery = (new Query($db))->from($table)->select('count(*)');

        $rowCountBefore = (int) $countQuery->createCommand()->queryScalar();

        $command = $db->createCommand($actualSQL, $actualParams);
        $this->assertEquals(1, $command->execute());

        $rowCountAfter = (int) $countQuery->createCommand()->queryScalar();

        $this->assertEquals(1, $rowCountAfter - $rowCountBefore);

        $command = $db->createCommand($actualSQL, $actualParams);
        $command->execute();
    }

    /**
     * @throws \Exception
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testOverrideParameters1(): void
    {
        $db = $this->getConnection();

        $params = [':id' => 1, ':pv2' => new Expression('(select type from {{%animal}}) where id=1')];
        $expression = new Expression('id = :id AND type = :pv2', $params);

        $query = new Query($db);
        $query->select('*')
            ->from('{{%animal}}')
            ->andWhere($expression)
            ->andWhere(['type' => new Param('test1', DataType::STRING)])
        ;

        $command = $query->createCommand();
        $this->assertCount(3, $command->getParams());
        $this->assertEquals([':id', ':pv2', ':pv2_0',], array_keys($command->getParams()));
        $this->assertEquals(
            DbHelper::replaceQuotes(
                'SELECT * FROM [[animal]] WHERE (id = 1 AND type = (select type from {{%animal}}) where id=1) AND ([[type]]=\'test1\')',
                $db->getDriverName()
            ),
            $command->getRawSql()
        );
    }

    /**
     * @throws \Exception
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testOverrideParameters2(): void
    {
        $db = $this->getConnection();

        $expression = new Expression('id = :qp1', [':qp1' => 1]);

        $query = new Query($db);
        $query->select('*')
            ->from('{{%animal}}')
            ->andWhere($expression)
            ->andWhere(['type' => 'test2'])
        ;

        $command = $query->createCommand();

        $this->assertCount(2, $command->getParams());
        $this->assertEquals([':qp1', ':qp1_0',], array_keys($command->getParams()));
        $this->assertEquals(
            DbHelper::replaceQuotes(
                'SELECT * FROM [[animal]] WHERE (id = 1) AND ([[type]]=\'test2\')',
                $db->getDriverName()
            ),
            $command->getRawSql()
        );
    }
}
