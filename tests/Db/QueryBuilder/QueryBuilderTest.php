<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ConnectionInterface;
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
use Yiisoft\Db\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;
use Yiisoft\Db\Tests\Support\Stub\QueryBuilder;

use function fclose;
use function fopen;
use function stream_context_create;

/**
 * @group db
 */
final class QueryBuilderTest extends IntegrationTestCase
{
    public function testBase(): void
    {
        $db = $this->getSharedConnection();

        $sql = (new Query($db))
            ->select('id')
            ->distinct()
            ->selectOption('TOP (10)')
            ->from('customer')
            ->leftJoin('profile', 'customer.id=profile.user_id')
            ->where('customer.age>19')
            ->groupBy('customer.group_id')
            ->having('COUNT(customer.id)>1')
            ->orderBy(['customer.name' => SORT_DESC])
            ->limit(20)
            ->for('UPDATE')
            ->createCommand()
            ->getRawSql();

        $this->assertSame(
            'SELECT DISTINCT TOP (10) [id] FROM [customer] LEFT JOIN [profile] ON customer.id=profile.user_id WHERE customer.age>19 GROUP BY [customer].[group_id] HAVING COUNT(customer.id)>1 ORDER BY [customer].[name] DESC LIMIT 20 FOR UPDATE',
            $sql,
        );
    }

    public function testAddDefaultValue(): void
    {
        $db = $this->getSharedConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::addDefaultValue is not supported by this DBMS.',
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
        $db = $this->getSharedConnection();
        $qb = new QueryBuilder($db);
        $params = [];

        $expected = $this->replaceQuotes($expected);

        try {
            $this->assertSame($expected, $qb->insertBatch($table, $rows, $columns, $params));
            Assert::arraysEquals($expectedParams, $params);
        } catch (InvalidArgumentException|Exception) {
        }
    }

    public function testBuildJoinException(): void
    {
        $db = $this->getSharedConnection();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A join clause must be specified as an array of join type, join table, and optionally join condition.',
        );

        $qb = $db->getQueryBuilder();
        $params = [];
        $qb->buildJoin(['admin_profile', 'admin_user.id = admin_profile.user_id'], $params);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getSharedConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::checkIntegrity is not supported by this DBMS.',
        );

        $qb = $db->getQueryBuilder();
        $qb->checkIntegrity('schema', 'table');
    }

    public function testCreateTable(): void
    {
        $db = $this->getSharedConnection();
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
                SQL,
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
        $db = $this->getSharedConnection();
        $subQuery = (new Query($db))->select('{{bar}}')->from('{{testCreateViewTable}}')->where(['>', 'bar', '5']);
        $qb = new QueryBuilder($db);

        $this->assertSame(
            <<<SQL
            CREATE VIEW [testCreateView] AS SELECT {{bar}} FROM {{testCreateViewTable}} WHERE [bar] > '5'
            SQL,
            $qb->createView('testCreateView', $subQuery),
        );
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::dropDefaultValue is not supported by this DBMS.',
        );

        $qb->dropDefaultValue('T_constraints_1', 'CN_pk');
    }

    public function testGetExpressionBuilderException(): void
    {
        $db = $this->getSharedConnection();

        $this->expectException(NotSupportedException::class);

        $expression = new class implements ExpressionInterface {};
        $qb = $db->getQueryBuilder();
        $qb->getExpressionBuilder($expression);
    }

    /**
     * @param (Closure(ConnectionInterface):(array|QueryInterface))|array|QueryInterface $columns
     */
    #[DataProviderExternal(QueryBuilderProvider::class, 'insert')]
    public function testInsert(
        string $table,
        Closure|array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams,
    ): void {
        $db = $this->getSharedConnection();
        $qb = new QueryBuilder($db);

        if ($columns instanceof Closure) {
            $columns = $columns($db);
        }

        $this->assertSame(
            $this->replaceQuotes($expectedSQL),
            $qb->insert($table, $columns, $params),
        );
        $this->assertEquals($expectedParams, $params);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertReturningPks')]
    public function testInsertReturningPks(
        string $table,
        Closure|array|QueryInterface $columns,
        array $params,
        string $expectedSql,
        array $expectedParams,
    ): void {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        if ($columns instanceof Closure) {
            $columns = $columns($db);
        }

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::insertReturningPks() is not supported by this DBMS.',
        );

        $qb->insertReturningPks($table, $columns, $params);
    }

    public function testResetSequence(): void
    {
        $db = $this->getSharedConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::resetSequence() is not supported by this DBMS.',
        );

        $qb->resetSequence('T_constraints_1', 'id');
    }

    /**
     * @param (Closure(ConnectionInterface):(array|ExpressionInterface|string|null))|array|ExpressionInterface|string|null $from
     */
    #[DataProviderExternal(QueryBuilderProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|ExpressionInterface|string $condition,
        Closure|array|ExpressionInterface|string|null $from,
        array $params,
        string $expectedSql,
        array $expectedParams = [],
    ): void {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        if ($from instanceof Closure) {
            $from = $from($db);
        }

        $sql = $qb->update($table, $columns, $condition, $from, $params);
        $sql = $db->getQuoter()->quoteSql($sql);

        $this->assertSame(
            $this->replaceQuotes($expectedSql),
            $sql,
        );
        $this->assertEquals($expectedParams, $params);

        $db->close();
    }

    /**
     * @param (Closure(ConnectionInterface):(array|QueryInterface))|array|QueryInterface $insertColumns
     */
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
    public function testUpsert(
        string $table,
        Closure|array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string|array $expectedSql,
        array $expectedParams,
    ): void {
        $db = $this->getSharedConnection();

        if ($insertColumns instanceof Closure) {
            $insertColumns = $insertColumns($db);
        }

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsert is not supported by this DBMS.',
        );

        $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns);
    }

    /**
     * @param (Closure(ConnectionInterface):(array|QueryInterface))|array|QueryInterface $insertColumns
     */
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
    public function testUpsertExecute(
        string $table,
        Closure|array|QueryInterface $insertColumns,
        array|bool $updateColumns,
    ): void {
        $db = $this->getSharedConnection();

        if ($insertColumns instanceof Closure) {
            $insertColumns = $insertColumns($db);
        }

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsert is not supported by this DBMS.',
        );

        $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns);
    }

    /**
     * @param (Closure(ConnectionInterface):(array|QueryInterface))|array|QueryInterface $insertColumns
     */
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertReturning')]
    public function testUpsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        ?array $returnColumns,
        string $expectedSql,
        array $expectedParams,
    ): void {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsertReturning() is not supported by this DBMS.',
        );

        $qb->upsertReturning($table, $insertColumns, $updateColumns, $returnColumns);
    }

    public function testBuildValueClosedResource(): void
    {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        $resource = fopen('php://memory', 'r');
        fclose($resource);
        $params = [];

        $this->expectExceptionObject(new InvalidArgumentException('Resource is closed.'));

        $qb->buildValue($resource, $params);
    }

    public function testPrepareValueClosedResource(): void
    {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        $resource = fopen('php://memory', 'r');
        fclose($resource);

        $this->expectExceptionObject(new InvalidArgumentException('Resource is closed.'));

        $qb->prepareValue($resource);
    }

    public function testPrepareValueNonStreamResource(): void
    {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        $this->expectExceptionObject(new InvalidArgumentException('Supported only stream resource type.'));

        $qb->prepareValue(stream_context_create());
    }

    public function testGetServerInfo(): void
    {
        $db = $this->getSharedConnection();
        $qb = $db->getQueryBuilder();

        $this->assertInstanceOf(ServerInfoInterface::class, $qb->getServerInfo());
    }

    public function testJsonColumn(): void
    {
        $db = $this->getSharedConnection();
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
        $db = $this->getSharedConnection();
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
    }
}
