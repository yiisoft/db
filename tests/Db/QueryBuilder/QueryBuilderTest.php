<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder;

use JsonException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stub\QueryBuilder;
use Yiisoft\Db\Tests\Support\Stub\Schema;
use Yiisoft\Db\Tests\Support\TestTrait;

use function fclose;
use function fopen;
use function stream_context_create;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    use TestTrait;

    /**
     * @throws Exception
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::addDefaultValue is not supported by this DBMS.'
        );

        $qb->addDefaultValue('table', 'name', 'column', 'value');
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::batchInsert
     */
    public function testBatchInsert(
        string $table,
        iterable $rows,
        array $columns,
        string $expected,
        array $expectedParams = [],
    ): void {
        $db = $this->getConnection();

        $schemaMock = $this->createMock(Schema::class);
        $qb = new QueryBuilder($db->getQuoter(), $schemaMock);
        $params = [];

        try {
            $this->assertSame($expected, $qb->insertBatch($table, $rows, $columns, $params));
            $this->assertSame($expectedParams, $params);
        } catch (InvalidArgumentException|Exception) {
        }
    }

    public function testBuildJoinException(): void
    {
        $db = $this->getConnection();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'A join clause must be specified as an array of join type, join table, and optionally join condition.',
        );

        $qb = $db->getQueryBuilder();
        $params = [];
        $qb->buildJoin(['admin_profile', 'admin_user.id = admin_profile.user_id'], $params);
    }

    /**
     * @throws Exception
     */
    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::checkIntegrity is not supported by this DBMS.'
        );

        $qb = $db->getQueryBuilder();
        $qb->checkIntegrity('schema', 'table');
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                CREATE TABLE [[test]] (
                \t[[id]] integer PRIMARY KEY AUTOINCREMENT,
                \t[[name]] varchar(255) NOT NULL,
                \t[[email]] varchar(255) NOT NULL,
                \t[[status]] integer NOT NULL,
                \t[[created_at]] datetime NOT NULL,
                \tUNIQUE test_email_unique (email)
                )
                SQL,
                $db->getDriverName(),
            ),
            $qb->createTable(
                'test',
                [
                    'id' => 'pk',
                    'name' => 'string(255) NOT NULL',
                    'email' => ColumnBuilder::string()->notNull(),
                    'status' => 'integer NOT NULL',
                    'created_at' => 'datetime NOT NULL',
                    'UNIQUE test_email_unique (email)',
                ],
            ),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $schemaMock = $this->createMock(Schema::class);
        $subQuery = (new Query($db))->select('{{bar}}')->from('{{testCreateViewTable}}')->where(['>', 'bar', '5']);
        $qb = new QueryBuilder($db->getQuoter(), $schemaMock);

        $this->assertSame(
            <<<SQL
            CREATE VIEW [testCreateView] AS SELECT {{bar}} FROM {{testCreateViewTable}} WHERE [bar] > '5'
            SQL,
            $qb->createView('testCreateView', $subQuery)
        );
    }

    /**
     * @throws Exception
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::dropDefaultValue is not supported by this DBMS.'
        );

        $qb->dropDefaultValue('T_constraints_1', 'CN_pk');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetExpressionBuilderException(): void
    {
        $db = $this->getConnection();

        $this->expectException(Exception::class);

        $expression = new class () implements ExpressionInterface {
        };
        $qb = $db->getQueryBuilder();
        $qb->getExpressionBuilder($expression);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::insert
     *
     * @throws Exception
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $schemaMock = $this->createMock(Schema::class);
        $qb = new QueryBuilder($db->getQuoter(), $schemaMock);

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
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::insertWithReturningPks() is not supported by this DBMS.'
        );

        $qb->insertWithReturningPks($table, $columns, $params);
    }

    /**
     * @throws Exception
     */
    public function testResetSequence(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::resetSequence() is not supported by this DBMS.'
        );

        $qb->resetSequence('T_constraints_1', 'id');
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::update
     *
     * @throws Exception
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        array $params,
        string $expectedSql,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $schemaMock = $this->createMock(Schema::class);
        $qb = new QueryBuilder($db->getQuoter(), $schemaMock);

        $sql = $qb->update($table, $columns, $condition, $params);
        $sql = $qb->quoter()->quoteSql($sql);

        $this->assertSame($expectedSql, $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::upsert
     *
     * @throws Exception
     * @throws JsonException
     * @throws NotSupportedException
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string|array $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $actualParams = [];

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsert is not supported by this DBMS.'
        );

        $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::upsert
     */
    public function testUpsertExecute(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsert is not supported by this DBMS.'
        );

        $actualParams = [];
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);
    }

    public function testPrepareValueClosedResource(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectExceptionObject(new InvalidArgumentException('Resource is closed.'));

        $resource = fopen('php://memory', 'r');
        fclose($resource);

        $qb->prepareValue($resource);
    }

    public function testPrepareValueNonStreamResource(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectExceptionObject(new InvalidArgumentException('Supported only stream resource type.'));

        $qb->prepareValue(stream_context_create());
    }
}
