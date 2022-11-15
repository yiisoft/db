<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Closure;
use JsonException;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Provider\ColumnTypesProvider;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

use function is_array;
use function str_replace;
use function str_starts_with;
use function strncmp;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonQueryBuilderTest extends AbstractQueryBuilderTest
{
    use TestTrait;

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnColumn('customer', 'id', 'Primary key.');
        $command->setSql($sql)->execute();
        $commentOnColumn = DbHelper::getCommmentsFromColumn('customer', 'id', $db);

        $this->assertSame('Primary key.', $commentOnColumn);
    }

    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnTable('customer', 'Customer table.');
        $command->setSql($sql)->execute();
        $commentOnTable = DbHelper::getCommmentsFromTable('customer', $db);

        $this->assertSame('Customer table.', $commentOnTable);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::addDropChecks()
     */
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($qb));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::addDropForeignKeys()
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($qb));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::addDropPrimaryKeys()
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($qb));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::addDropUniques()
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($qb));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::alterColumn()
     */
    public function testAlterColumn(
        string $table,
        string $column,
        ColumnSchemaBuilder|string $type,
        string $expected
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->alterColumn($table, $column, $type);

        $this->assertSame($expected, $sql);
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
        $db = $this->getConnectionWithData();

        $qb = $db->getQueryBuilder();
        $params = [];
        $sql = $qb->batchInsert($table, $columns, $value, $params);

        $this->assertSame($expected, $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildConditions()
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $conditions,
        string $expected,
        array $expectedParams = []
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->where($conditions);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            'SELECT *' . (
                empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes($expected, $db->getName())
            ),
            $sql,
        );
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildFilterConditions()
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = $this->getQuery($db)->filterWhere($condition);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            'SELECT *' . (
                empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes($expected, $db->getName())
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
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $params = [];
        $sql = $qb->buildFrom([$table], $params);
        $replacedQuotes = DbHelper::replaceQuotes($expected, $db->getName());

        $this->assertIsString($replacedQuotes);
        $this->assertSame('FROM ' . $replacedQuotes, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildLikeConditions()
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $query = $this->getQuery($db)->where($condition);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $replacedQuotes = DbHelper::replaceQuotes($expected, $db->getName());

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replacedQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildWhereExists()
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $expectedQueryParams = [];
        $subQuery = $this->getQuery($db)->select('1')->from('Website w');
        $query = $this->getQuery($db)->select('id')->from('TotalExample t')->where([$cond, $subQuery]);

        [$actualQuerySql, $actualQueryParams] = $qb->build($query);

        $this->assertSame($expectedQuerySql, $actualQuerySql);
        $this->assertSame($expectedQueryParams, $actualQueryParams);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();
        $sql = $qb->checkIntegrity('schema', 'table');

        $this->assertSame(0, $command->setSql($sql)->execute());
    }

    public function testCheckIntegrityExecuteException(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();
        $schemaName = 'dbo';
        $tableName = 'T_constraints_3';
        $command->setSql($qb->checkIntegrity($schemaName, $tableName, false))->execute();
        $command->setSql(
            <<<SQL
            INSERT INTO {{{$tableName}}} ([[C_id]], [[C_fk_id_1]], [[C_fk_id_2]]) VALUES (1, 2, 3)
            SQL
        )->execute();
        $command->setSql($qb->checkIntegrity($schemaName, $tableName))->execute();

        $this->expectException(IntegrityException::class);

        $command->setSql(
            <<<SQL
            INSERT INTO {{{$tableName}}} ([[C_id]], [[C_fk_id_1]], [[C_fk_id_2]]) VALUES (1, 2, 3)
            SQL
        )->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::createDropIndex()
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($qb));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();

        if ($db->getSchema()->getTableSchema('testCreateTable', true) !== null) {
            $command->dropTable('testCreateTable')->execute();
        }

        $sql = $qb->createTable('testCreateTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER]);
        $command->setSql($sql)->execute();
        $command->insert('testCreateTable', ['bar' => 1])->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[id]], [[bar]] FROM {{testCreateTable}};
            SQL
        )->queryAll();

        $this->assertEquals([['id' => 1, 'bar' => 1]], $records);
    }

    public function testCreateTableColumnTypes(): void
    {
        $db = $this->getConnectionWithData();

        $qb = $db->getQueryBuilder();

        if ($db->getTableSchema('column_type_table', true) !== null) {
            $db->createCommand($qb->dropTable('column_type_table'))->execute();
        }

        $columnTypes = (new ColumnTypesProvider())->columnTypes($db);
        $columns = [];
        $i = 0;

        foreach ($columnTypes as [$column, $builder, $expected]) {
            if (
                !(
                    strncmp($column, Schema::TYPE_PK, 2) === 0 ||
                    strncmp($column, Schema::TYPE_UPK, 3) === 0 ||
                    strncmp($column, Schema::TYPE_BIGPK, 5) === 0 ||
                    strncmp($column, Schema::TYPE_UBIGPK, 6) === 0 ||
                    str_starts_with(substr($column, -5), 'FIRST')
                )
            ) {
                $columns['col' . ++$i] = str_replace('CHECK (value', 'CHECK ([[col' . $i . ']]', $column);
            }
        }

        $db->createCommand($qb->createTable('column_type_table', $columns))->execute();

        $this->assertNotEmpty($db->getTableSchema('column_type_table', true));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::delete()
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

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnColumn('customer', 'id', 'Primary key.');
        $command->setSql($sql)->execute();
        $commentOnColumn = DbHelper::getCommmentsFromColumn('customer', 'id', $db);

        $this->assertSame('Primary key.', $commentOnColumn);

        $sql = $qb->dropCommentFromColumn('customer', 'id');
        $command->setSql($sql)->execute();
        $commentOnColumn = DbHelper::getCommmentsFromColumn('customer', 'id', $db);

        $this->assertSame([], $commentOnColumn);
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();
        $sql = $qb->addCommentOnTable('customer', 'Customer table.');
        $command->setSql($sql)->execute();
        $commentOnTable = DbHelper::getCommmentsFromTable('customer', $db);

        $this->assertSame('Customer table.', $commentOnTable);

        $sql = $qb->dropCommentFromTable('customer');
        $command->setSql($sql)->execute();
        $commentOnTable = DbHelper::getCommmentsFromTable('customer', $db);

        $this->assertSame([], $commentOnTable);
    }

    public function testGetColumnType(): void
    {
        $db = $this->getConnection();

        $columnTypes = (new ColumnTypesProvider())->columnTypes($db);
        $qb = $db->getQueryBuilder();

        foreach ($columnTypes as $item) {
            [$column, $builder, $expected] = $item;

            $driverName = $db->getName();

            if (isset($item[3][$driverName])) {
                $expectedColumnSchemaBuilder = $item[3][$driverName];
            } elseif (isset($item[3]) && !is_array($item[3])) {
                $expectedColumnSchemaBuilder = $item[3];
            } else {
                $expectedColumnSchemaBuilder = $column;
            }

            $this->assertSame($expectedColumnSchemaBuilder, $builder->__toString());
            $this->assertSame($expected, $qb->getColumnType($column));
            $this->assertSame($expected, $qb->getColumnType($builder));
        }
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::insert()
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
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::insertEx()
     */
    public function testInsertEx(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnectionWithData();

        $qb = $db->getQueryBuilder();

        $this->assertSame($expectedSQL, $qb->insertEx($table, $columns, $params));
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testRenameTable(): void
    {
        $db = $this->getConnectionWithData();

        $fromTableName = 'type';
        $toTableName = 'new_type';
        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();

        if ($db->getSchema()->getTableSchema($toTableName) !== null) {
            $command->dropTable($toTableName)->execute();
        }

        $this->assertNotNull($db->getSchema()->getTableSchema($fromTableName));
        $this->assertNull($db->getSchema()->getTableSchema($toTableName));

        $sql = $qb->renameTable($fromTableName, $toTableName);
        $command->setSql($sql)->execute();

        $this->assertNull($db->getSchema()->getTableSchema($fromTableName, true));
        $this->assertNotNull($db->getSchema()->getTableSchema($toTableName, true));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::update()
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
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::upsert()
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
        $db = $this->getConnectionWithData();

        $actualParams = [];
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);

        if (is_string($expectedSQL)) {
            $this->assertSame($expectedSQL, $actualSQL);
        } else {
            $this->assertContains($actualSQL, $expectedSQL);
        }

        if (ArrayHelper::isAssociative($expectedParams)) {
            $this->assertSame($expectedParams, $actualParams);
        } else {
            Assert::isOneOf($actualParams, $expectedParams);
        }
    }
}
