<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder;

use JsonException;
use Yiisoft\Db\Connection\ServerInfoInterface;
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
        $db->close();
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
        $qb = new QueryBuilder($db);

        try {
            $statements = $qb->insertBatch($table, $rows, $columns);

            if (empty($expected)) {
                $this->assertCount(0, $statements);
            } else {
                $this->assertSame($expected, $statements[0]->sql);
            }
            if (!empty($statements)) {
                $this->assertSame($expectedParams, $statements[0]->params);
            }
        } catch (InvalidArgumentException|Exception) {
        } finally {
            $db->close();
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
        $db->close();
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
        $db->close();
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
        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testCreateView(): void
    {
        $db = $this->getConnection();
        $subQuery = (new Query($db))->select('{{bar}}')->from('{{testCreateViewTable}}')->where(['>', 'bar', '5']);
        $qb = new QueryBuilder($db);

        $this->assertSame(
            <<<SQL
            CREATE VIEW [testCreateView] AS SELECT {{bar}} FROM {{testCreateViewTable}} WHERE [bar] > '5'
            SQL,
            $qb->createView('testCreateView', $subQuery)
        );
        $db->close();
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
        $db->close();
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
        $db->close();
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
        $qb = new QueryBuilder($db);

        $this->assertSame($expectedSQL, $qb->insert($table, $columns, $params));
        $this->assertEquals($expectedParams, $params);
        $db->close();
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
        $db->close();
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
        $db->close();
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
        $qb = $db->getQueryBuilder();

        $sql = $qb->update($table, $columns, $condition, $params);
        $sql = $db->getQuoter()->quoteSql($sql);

        $this->assertSame($expectedSql, $sql);
        $this->assertEquals($expectedParams, $params);
        $db->close();
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
        $db->close();
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
        $db->close();
    }

    public function testPrepareValueClosedResource(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectExceptionObject(new InvalidArgumentException('Resource is closed.'));

        $resource = fopen('php://memory', 'r');
        fclose($resource);

        $qb->prepareValue($resource);
        $db->close();
    }

    public function testPrepareValueNonStreamResource(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectExceptionObject(new InvalidArgumentException('Supported only stream resource type.'));

        $qb->prepareValue(stream_context_create());
        $db->close();
    }

    public function testGetServerInfo(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->assertInstanceOf(ServerInfoInterface::class, $qb->getServerInfo());
        $db->close();
    }

    public function testJsonColumn(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();
        $column = ColumnBuilder::json();

        $this->assertSame(
            DbHelper::replaceQuotes(
                "CREATE TABLE [json_table] (\n\t[json_col] json CHECK (json_valid([json_col]))\n)",
                $db->getDriverName(),
            ),
            $qb->createTable('json_table', ['json_col' => $column]),
        );

        $this->assertSame(
            DbHelper::replaceQuotes(
                'ALTER TABLE [json_table] ADD [json_col] json',
                $db->getDriverName(),
            ),
            $qb->addColumn('json_table', 'json_col', $column),
        );

        $this->assertSame(
            DbHelper::replaceQuotes(
                'ALTER TABLE [json_table] CHANGE [json_col] [json_col] json',
                $db->getDriverName(),
            ),
            $qb->alterColumn('json_table', 'json_col', $column),
        );
        $db->close();
    }
}
