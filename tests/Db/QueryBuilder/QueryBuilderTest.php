<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\Stub\QueryBuilder;
use Yiisoft\Db\Tests\Support\TestTrait;

use function fclose;
use function fopen;
use function stream_context_create;

/**
 * @group db
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    use TestTrait;

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

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $rows,
        array $columns,
        string $expected,
        array $expectedParams = [],
    ): void {
        $db = $this->getConnection();
        $qb = new QueryBuilder($db);
        $params = [];

        try {
            $this->assertSame($expected, $qb->insertBatch($table, $rows, $columns, $params));
            Assert::arraysEquals($expectedParams, $params);
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
            self::replaceQuotes(
                <<<SQL
                CREATE TABLE [[test]] (
                \t[[id]] integer PRIMARY KEY AUTOINCREMENT,
                \t[[name]] string(255) NOT NULL,
                \t[[email]] varchar(255) NOT NULL,
                \t[[status]] integer NOT NULL,
                \t[[created_at]] datetime NOT NULL,
                \tUNIQUE test_email_unique (email)
                )
                SQL
            ),
            $qb->createTable(
                'test',
                [
                    'id' => 'pk',
                    'name' => new Expression('string(255) NOT NULL'),
                    'email' => ColumnBuilder::string()->notNull(),
                    'status' => new IntegerColumn(notNull: true),
                    'created_at' => 'datetime NOT NULL',
                    'UNIQUE test_email_unique (email)',
                ],
            ),
        );
    }

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
    }

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

    public function testGetExpressionBuilderException(): void
    {
        $db = $this->getConnection();

        $this->expectException(InvalidArgumentException::class);

        $expression = new class () implements ExpressionInterface {
        };
        $qb = $db->getQueryBuilder();
        $qb->getExpressionBuilder($expression);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insert')]
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
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertReturningPks')]
    public function testInsertReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSql,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::insertReturningPks() is not supported by this DBMS.'
        );

        $qb->insertReturningPks($table, $columns, $params);
    }

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

    #[DataProviderExternal(QueryBuilderProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        array $params,
        string $expectedSql,
        array $expectedParams = [],
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $sql = $qb->update($table, $columns, $condition, $params);
        $sql = $db->getQuoter()->quoteSql($sql);

        $this->assertSame($expectedSql, $sql);
        $this->assertEquals($expectedParams, $params);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string|array $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsert is not supported by this DBMS.'
        );

        $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
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

        $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertReturning')]
    public function testUpsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array|null $returnColumns,
        string $expectedSql,
        array $expectedParams
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsertReturning() is not supported by this DBMS.'
        );

        $qb->upsertReturning($table, $insertColumns, $updateColumns, $returnColumns);
    }

    public function testBuildValueClosedResource(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $resource = fopen('php://memory', 'r');
        fclose($resource);
        $params = [];

        $this->expectExceptionObject(new InvalidArgumentException('Resource is closed.'));

        $qb->buildValue($resource, $params);
    }

    public function testPrepareValueClosedResource(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $resource = fopen('php://memory', 'r');
        fclose($resource);

        $this->expectExceptionObject(new InvalidArgumentException('Resource is closed.'));

        $qb->prepareValue($resource);
    }

    public function testPrepareValueNonStreamResource(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectExceptionObject(new InvalidArgumentException('Supported only stream resource type.'));

        $qb->prepareValue(stream_context_create());
    }

    public function testGetServerInfo(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->assertInstanceOf(ServerInfoInterface::class, $qb->getServerInfo());
    }

    public function testJsonColumn(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();
        $column = ColumnBuilder::json();

        $this->assertSame(
            self::replaceQuotes("CREATE TABLE [json_table] (\n\t[json_col] json CHECK (json_valid([json_col]))\n)"),
            $qb->createTable('json_table', ['json_col' => $column]),
        );

        $this->assertSame(
            self::replaceQuotes('ALTER TABLE [json_table] ADD [json_col] json'),
            $qb->addColumn('json_table', 'json_col', $column),
        );

        $this->assertSame(
            self::replaceQuotes('ALTER TABLE [json_table] CHANGE [json_col] [json_col] json'),
            $qb->alterColumn('json_table', 'json_col', $column),
        );
    }

    public function testWithTypecasting(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $dmlBuilder = Assert::getPropertyValue($qb, 'dmlBuilder');
        $typecasting = Assert::getPropertyValue($dmlBuilder, 'typecasting');

        $this->assertTrue($typecasting);

        $dmlBuilder = $dmlBuilder->withTypecasting(false);
        $typecasting = Assert::getPropertyValue($dmlBuilder, 'typecasting');

        $this->assertFalse($typecasting);

        $dmlBuilder = $dmlBuilder->withTypecasting();
        $typecasting = Assert::getPropertyValue($dmlBuilder, 'typecasting');

        $this->assertTrue($typecasting);

        $qb = $qb->withTypecasting(false);
        $dmlBuilder = Assert::getPropertyValue($qb, 'dmlBuilder');
        $typecasting = Assert::getPropertyValue($dmlBuilder, 'typecasting');

        $this->assertFalse($typecasting);

        $qb = $qb->withTypecasting();
        $dmlBuilder = Assert::getPropertyValue($qb, 'dmlBuilder');
        $typecasting = Assert::getPropertyValue($dmlBuilder, 'typecasting');

        $this->assertTrue($typecasting);

        $db->close();
    }
}
