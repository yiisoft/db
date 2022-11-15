<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PDO;
use ReflectionException;
use Throwable;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\AbstractCommandTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

use function is_resource;
use function serialize;
use function setlocale;
use function stream_get_contents;
use function time;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonCommandTest extends AbstractCommandTest
{
    use TestTrait;

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->addCommentOnColumn('customer', 'id', 'Primary key.')->execute();
        $commentOnColumn = DbHelper::getCommmentsFromColumn('customer', 'id', $db);

        $this->assertSame('Primary key.', $commentOnColumn);
    }

    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->addCommentOnTable('customer', 'Customer table.')->execute();
        $commentOnTable = DbHelper::getCommmentsFromTable('customer', $db);

        $this->assertSame('Customer table.', $commentOnTable);
    }

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $sql = $command->addDefaultValue('', 'customer', 'id', '1')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[customer]] ADD CONSTRAINT [] DEFAULT '1' FOR [[id]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddDropForeignKey(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_fk';
        $name = 'test_fk_constraint';
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable(
            $tableName,
            [
                'int1' => 'integer not null unique',
                'int2' => 'integer not null unique',
                'int3' => 'integer not null unique',
                'int4' => 'integer not null unique',
                'unique ([[int1]], [[int2]])',
                'unique ([[int3]], [[int4]])',
            ]
        )->execute();

        $this->assertEmpty($schema->getTableForeignKeys($tableName, true));

        $command->addForeignKey($name, $tableName, ['int1'], $tableName, ['int3'])->execute();

        $this->assertSame(['int1'], $schema->getTableForeignKeys($tableName, true)[0]->getColumnNames());
        $this->assertSame(['int3'], $schema->getTableForeignKeys($tableName, true)[0]->getForeignColumnNames());

        $command->dropForeignKey($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableForeignKeys($tableName, true));

        $command->addForeignKey($name, $tableName, ['int1', 'int2'], $tableName, ['int3', 'int4'])->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableForeignKeys($tableName, true)[0]->getColumnNames());
        $this->assertSame(['int3', 'int4'], $schema->getTableForeignKeys($tableName, true)[0]->getForeignColumnNames());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddDropUnique(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_uq';
        $name = 'test_uq_constraint';
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableUniques($tableName, true));

        $command->addUnique($name, $tableName, ['int1'])->execute();

        $this->assertSame(['int1'], $schema->getTableUniques($tableName, true)[0]->getColumnNames());

        $command->dropUnique($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableUniques($tableName, true));

        $command->addUnique($name, $tableName, ['int1', 'int2'])->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableUniques($tableName, true)[0]->getColumnNames());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAlterTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('testAlterTable', true) !== null) {
            $command->dropTable('testAlterTable')->execute();
        }

        $command->createTable('testAlterTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER])->execute();
        $command->insert('testAlterTable', ['bar' => 1])->execute();
        $command->alterColumn('testAlterTable', 'bar', Schema::TYPE_STRING)->execute();
        $command->insert('testAlterTable', ['bar' => 'hello'])->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[id]], [[bar]] FROM {{testAlterTable}}
            SQL
        )->queryAll();

        $this->assertSame([['id' => 1, 'bar' => 1], ['id' => 2, 'bar' => 'hello']], $records);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testBatchInsert(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->batchInsert(
            'table',
            ['column1', 'column2'],
            [['value1', 'value2'], ['value3', 'value4']],
        )->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                INSERT INTO [[table]] ([[column1]], [[column2]]) VALUES (:qp0, :qp1), (:qp2, :qp3)
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    /**
     * Test batch insert with different data types.
     *
     * Ensure double is inserted with `.` decimal separator.
     *
     * @link https://github.com/yiisoft/yii2/issues/6526
     */
    public function testBatchInsertDataTypesLocale(): void
    {
        $locale = setlocale(LC_NUMERIC, 0);

        if ($locale === false) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        $db = $this->getConnectionWithData();

        try {
            /* This one sets decimal mark to comma sign */
            setlocale(LC_NUMERIC, 'ru_RU.utf8');

            $cols = ['int_col', 'char_col', 'float_col', 'bool_col'];
            $data = [[1, 'A', 9.735, true], [2, 'B', -2.123, false], [3, 'C', 2.123, false]];

            /* clear data in "type" table */
            $db->createCommand()->delete('type')->execute();

            /* change, for point oracle. */
            if ($db->getName() === 'oci') {
                $db->createCommand("ALTER SESSION SET NLS_NUMERIC_CHARACTERS='.,'")->execute();
            }

            /* batch insert on "type" table */
            $db->createCommand()->batchInsert('type', $cols, $data)->execute();
            $data = $db->createCommand(
                <<<SQL
                SELECT [[int_col]], [[char_col]], [[float_col]], [[bool_col]] FROM {{type}} WHERE [[int_col]] IN (1,2,3) ORDER BY [[int_col]]
                SQL
            )->queryAll();

            $this->assertCount(3, $data);
            $this->assertEquals(1, $data[0]['int_col']);
            $this->assertEquals(2, $data[1]['int_col']);
            $this->assertEquals(3, $data[2]['int_col']);

            /* rtrim because Postgres padds the column with whitespace */
            $this->assertSame('A', rtrim($data[0]['char_col']));
            $this->assertSame('B', rtrim($data[1]['char_col']));
            $this->assertSame('C', rtrim($data[2]['char_col']));
            $this->assertEquals(9.735, $data[0]['float_col']);
            $this->assertEquals(-2.123, $data[1]['float_col']);
            $this->assertEquals(2.123, $data[2]['float_col']);
            $this->assertEquals(1, $data[0]['bool_col']);
            Assert::isOneOf($data[1]['bool_col'], ['0', false]);
            Assert::isOneOf($data[2]['bool_col'], ['0', false]);
        } catch (Exception | Throwable $e) {
            setlocale(LC_NUMERIC, $locale);

            throw $e;
        }

        setlocale(LC_NUMERIC, $locale);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsertFailsOld(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            [['t1@example.com', 'test_name', 'test_address']],
        );

        $this->assertSame(1, $command->execute());

        $result = $this->getQuery($db)
            ->select(['email', 'name', 'address'])
            ->from('{{customer}}')
            ->where(['=', '[[email]]', 't1@example.com'])
            ->one();

        $this->assertCount(3, $result);
        $this->assertSame(['email' => 't1@example.com', 'name' => 'test_name', 'address' => 'test_address'], $result);
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::batchInsertSql()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsertSQL(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->batchInsert($table, $columns, $values);
        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());

        $command->execute();

        $this->assertEquals($insertedRow, (new Query($db))->from($table)->count());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsertWithManyData(): void
    {
        $db = $this->getConnectionWithData();

        $values = [];
        $attemptsInsertRows = 200;
        $command = $db->createCommand();

        for ($i = 0; $i < $attemptsInsertRows; $i++) {
            $values[$i] = ['t' . $i . '@any.com', 't' . $i, 't' . $i . ' address'];
        }

        $command->batchInsert('{{customer}}', ['email', 'name', 'address'], $values);

        $this->assertSame($attemptsInsertRows, $command->execute());

        $insertedRowsCount = $this->getQuery($db)->from('{{customer}}')->count();

        $this->assertGreaterThanOrEqual($attemptsInsertRows, $insertedRowsCount);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsertWithYield(): void
    {
        $db = $this->getConnectionWithData();

        $rows = (
            static function () {
                yield ['test@email.com', 'test name', 'test address'];
            }
        )();
        $command = $db->createCommand();
        $command->batchInsert('{{customer}}', ['email', 'name', 'address'], $rows);

        $this->assertSame(1, $command->execute());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBindParam(): void
    {
        $db = $this->getConnectionWithData();

        $params = 1;
        $sql = <<<SQL
        SELECT * FROM customer WHERE id = :id
        SQL;
        $command = $db->createCommand();
        $command->setSql($sql);
        $command->bindParam(':id', $params);

        $this->assertSame($sql, $command->getSql());
        $this->assertEquals(
            [
                'id' => 1,
                'email' => 'user1@example.com',
                'name' => 'user1',
                'address' => 'address1',
                'status' => 1,
                'profile_id' => 1,
            ],
            $command->queryOne(),
        );

        $command->setSql($sql);
        $command->bindParam(':id', $params, PDO::PARAM_INT);

        $this->assertSame($sql, $command->getSql());
        $this->assertEquals(
            [
                'id' => 1,
                'email' => 'user1@example.com',
                'name' => 'user1',
                'address' => 'address1',
                'status' => 1,
                'profile_id' => 1,
            ],
            $command->queryOne(),
        );

        $command->setSql($sql);
        $command->bindParam(':id', $params, PDO::PARAM_INT);

        $this->assertSame($sql, $command->getSql());
        $this->assertEquals(
            [
                'id' => 1,
                'email' => 'user1@example.com',
                'name' => 'user1',
                'address' => 'address1',
                'status' => 1,
                'profile_id' => 1,
            ],
            $command->queryOne(),
        );

        $command->setSql($sql);
        $command->bindParam(':id', $params, PDO::PARAM_INT, null, [PDO::ATTR_STRINGIFY_FETCHES => true]);

        $this->assertSame($sql, $command->getSql());
        $this->assertEquals(
            [
                'id' => 1,
                'email' => 'user1@example.com',
                'name' => 'user1',
                'address' => 'address1',
                'status' => 1,
                'profile_id' => 1,
            ],
            $command->queryOne(),
        );
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::bindParamsNonWhere()
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getConnectionWithData();

        $db->createCommand()->insert(
            'customer',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1',
            ]
        )->execute();
        $params = [':email' => 'testParams@example.com', ':len' => 5];
        $command = $db->createCommand($sql, $params);

        $this->assertSame('Params', $command->queryScalar());
    }

    public function testBindParamValue(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();

        // bindParam
        $command = $command->setSql(
            <<<SQL
            INSERT INTO customer(email, name, address) VALUES (:email, :name, :address)
            SQL
        );
        $email = 'user4@example.com';
        $name = 'user4';
        $address = 'address4';
        $command->bindParam(':email', $email);
        $command->bindParam(':name', $name);
        $command->bindParam(':address', $address);
        $command->execute();
        $command = $command->setSql(
            <<<SQL
            SELECT name FROM customer WHERE email=:email
            SQL,
        );
        $command->bindParam(':email', $email);

        $this->assertSame($name, $command->queryScalar());

        // bindValue
        $command = $command->setSql(
            <<<SQL
            INSERT INTO customer(email, name, address) VALUES (:email, 'user5', 'address5')
            SQL
        );
        $command->bindValue(':email', 'user5@example.com');
        $command->execute();
        $command = $command->setSql(
            <<<SQL
            SELECT email FROM customer WHERE name=:name
            SQL
        );

        $command->bindValue(':name', 'user5');

        $this->assertSame('user5@example.com', $command->queryScalar());
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->assertSame(0, $command->checkIntegrity('schema', 'table')->execute());
    }

    public function testCheckIntegrityExecuteException(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $schemaName = 'dbo';
        $tableName = 'T_constraints_3';
        $command->checkIntegrity($schemaName, $tableName, false)->execute();
        $sql = <<<SQL
        INSERT INTO {{{$tableName}}} ([[C_id]], [[C_fk_id_1]], [[C_fk_id_2]]) VALUES (1, 2, 3)
        SQL;
        $command->setSql($sql)->execute();
        $db->createCommand()->checkIntegrity($schemaName, $tableName)->execute();

        $this->expectException(IntegrityException::class);

        $command->setSql($sql)->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testColumnCase(): void
    {
        $db = $this->getConnectionWithData();

        $this->assertSame(PDO::CASE_NATURAL, $db->getActivePDO()->getAttribute(PDO::ATTR_CASE));

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT [[customer_id]], [[total]] FROM {{order}}
        SQL;
        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['CUSTOMER_ID']));
        $this->assertTrue(isset($rows[0]['TOTAL']));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testCreateDropIndex(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_idx';
        $name = 'test_idx_constraint';
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $command->createIndex($name, $tableName, ['int1'])->execute();

        $this->assertSame(['int1'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertFalse($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $command->dropIndex($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $command->createIndex($name, $tableName, ['int1', 'int2'])->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertFalse($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $command->dropIndex($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));
        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $command->createIndex($name, $tableName, ['int1'], QueryBuilder::INDEX_UNIQUE)->execute();

        $this->assertSame(['int1'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $command->dropIndex($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $command->createIndex($name, $tableName, ['int1', 'int2'], QueryBuilder::INDEX_UNIQUE)->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());
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

        if ($db->getSchema()->getTableSchema('testCreateTable', true) !== null) {
            $command->dropTable('testCreateTable')->execute();
        }

        $command->createTable('testCreateTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER])->execute();
        $command->insert('testCreateTable', ['bar' => 1])->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[id]], [[bar]] FROM {{testCreateTable}};
            SQL
        )->queryAll();

        $this->assertEquals([['id' => 1, 'bar' => 1]], $records);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $subQuery = $this->getQuery($db)->select('bar')->from('testCreateViewTable')->where(['>', 'bar', '5']);

        if ($db->getSchema()->getTableSchema('testCreateView') !== null) {
            $command->dropView('testCreateView')->execute();
        }

        if ($db->getSchema()->getTableSchema('testCreateViewTable')) {
            $command->dropTable('testCreateViewTable')->execute();
        }

        $command
            ->createTable('testCreateViewTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER])->execute();
        $command->insert('testCreateViewTable', ['bar' => 1])->execute();
        $command->insert('testCreateViewTable', ['bar' => 6])->execute();
        $command->createView('testCreateView', $subQuery)->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[bar]] FROM {{testCreateView}};
            SQL
        )->queryAll();

        $this->assertEquals([['bar' => 6]], $records);
    }

    public function testDataReaderRewindException(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(InvalidCallException::class);
        $this->expectExceptionMessage('DataReader cannot rewind. It is a forward-only reader.');

        $command = $db->createCommand();
        $reader = $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        )->query();
        $reader->next();
        $reader->rewind();
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->addCommentOnColumn('customer', 'id', 'Primary key.')->execute();
        $commentOnColumn = DbHelper::getCommmentsFromColumn('customer', 'id', $db);

        $this->assertSame('Primary key.', $commentOnColumn);

        $command->dropCommentFromColumn('customer', 'id')->execute();
        $commentOnColumn = DbHelper::getCommmentsFromColumn('customer', 'id', $db);

        $this->assertSame([], $commentOnColumn);
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->addCommentOnTable('customer', 'Customer table.')->execute();
        $commentOnTable = DbHelper::getCommmentsFromTable('customer', $db);

        $this->assertSame('Customer table.', $commentOnTable);

        $command->dropCommentFromTable('customer')->execute();
        $commentOnTable = DbHelper::getCommmentsFromTable('customer', $db);

        $this->assertSame([], $commentOnTable);
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $sql = $command->dropDefaultValue('char_col2', 'type')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[type]] DROP CONSTRAINT [[char_col2]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropTable(): void
    {
        $db = $this->getConnectionWithData();

        $tableName = 'type';

        $this->assertNotNull($db->getSchema()->getTableSchema($tableName));

        $db->createCommand()->dropTable($tableName)->execute();

        $this->assertNull($db->getSchema()->getTableSchema($tableName));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropView(): void
    {
        $db = $this->getConnectionWithData();

        /* since it already exists in the fixtures */
        $viewName = 'animal_view';

        $this->assertNotNull($db->getSchema()->getTableSchema($viewName));

        $db->createCommand()->dropView($viewName)->execute();

        $this->assertNull($db->getSchema()->getTableSchema($viewName));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testExecute(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            INSERT INTO {{customer}}([[email]], [[name]], [[address]]) VALUES ('user4@example.com', 'user4', 'address4')
            SQL
        );

        $this->assertSame(1, $command->execute());

        $command = $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM {{customer}} WHERE [[name]] = 'user4'
            SQL
        );

        $this->assertEquals(1, $command->queryScalar());

        $command->setSql('bad SQL');
        $message = match ($db->getName()) {
            'sqlite' => 'SQLSTATE[HY000]: General error: 1 near "bad": syntax error',
            'sqlsrv' => "SQLSTATE[42000]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Could not find stored procedure 'bad'",
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        $command->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsert(): void
    {
        $db = $this->getConnectionWithData();

        $db->createCommand(
            <<<SQL
            DELETE FROM {{customer}}
            SQL
        )->execute();
        $command = $db->createCommand();
        $command
            ->insert('{{customer}}', ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'])
            ->execute();

        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{customer}};
                SQL
            )->queryScalar(),
        );

        $record = $db->createCommand(
            <<<SQL
            SELECT [[email]], [[name]], [[address]] FROM {{customer}}
            SQL
        )->queryOne();

        $this->assertSame(['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'], $record);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertExpression(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            DELETE FROM {{order_with_null_fk}}
            SQL
        )->execute();
        $expression = match ($db->getName()) {
            'mysql' => 'YEAR(NOW())',
            'pgsql' => "EXTRACT(YEAR FROM TIMESTAMP 'now')",
            'sqlite' => "strftime('%Y')",
            'sqlsrv' => 'YEAR(GETDATE())',
        };
        $command
            ->insert('{{order_with_null_fk}}', ['created_at' => new Expression($expression), 'total' => 1])
            ->execute();

        $this->assertEquals(1, $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM {{order_with_null_fk}}
            SQL
        )->queryScalar());

        $record = $command->setSql(
            <<<SQL
            SELECT [[created_at]] FROM {{order_with_null_fk}}
            SQL
        )->queryOne();

        $this->assertEquals(['created_at' => date('Y')], $record);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testsInsertQueryAsColumnValue(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $time = time();
        $command->setSql(
            <<<SQL
            DELETE FROM {{order_with_null_fk}}
            SQL
        )->execute();
        $command->insert('{{order}}', ['customer_id' => 1, 'created_at' => $time, 'total' => 42])->execute();

        if ($db->getName() === 'pgsql') {
            $orderId = $db->getLastInsertID('public.order_id_seq');
        } else {
            $orderId = $db->getLastInsertID();
        }

        $columnValueQuery = $this->getQuery($db)->select('created_at')->from('{{order}}')->where(['id' => $orderId]);
        $command
            ->insert(
                '{{order_with_null_fk}}',
                ['customer_id' => $orderId, 'created_at' => $columnValueQuery, 'total' => 42]
            )
            ->execute();

        $this->assertEquals(
            $time,
            $command->setSql(
                <<<SQL
                SELECT [[created_at]] FROM {{order_with_null_fk}} WHERE [[customer_id]] = :id
                SQL
            )->bindValues([':id' => $orderId])->queryScalar(),
        );

        $command->setSql(
            <<<SQL
            DELETE FROM {{order_with_null_fk}}
            SQL
        )->execute();
        $command->setSql(
            <<<SQL
            DELETE FROM {{order}}
            SQL
        )->execute();
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertSelect(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            DELETE FROM {{customer}}
            SQL
        )->execute();
        $command->insert(
            '{{customer}}',
            ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address']
        )->execute();
        $query = $this->getQuery($db)
            ->select(['{{customer}}.[[email]] as name', '[[name]] as email', '[[address]]'])
            ->from('{{customer}}')
            ->where(['and', ['<>', 'name', 'foo'], ['status' => [0, 1, 2, 3]]]);
        $command->insert('{{customer}}', $query)->execute();

        $this->assertEquals(
            2,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM {{customer}}
                SQL
            )->queryScalar(),
        );

        $record = $command->setSql(
            <<<SQL
            SELECT [[email]], [[name]], [[address]] FROM {{customer}}
            SQL
        )->queryAll();

        $this->assertSame(
            [
                ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'],
                ['email' => 'test', 'name' => 't1@example.com', 'address' => 'test address'],
            ],
            $record,
        );
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with alias syntax.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertSelectAlias(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            DELETE FROM {{customer}}
            SQL
        )->execute();
        $command->insert(
            '{{customer}}',
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ]
        )->execute();
        $query = $this->getQuery($db)
            ->select(['email' => '{{customer}}.[[email]]', 'address' => 'name', 'name' => 'address'])
            ->from('{{customer}}')
            ->where(['and', ['<>', 'name', 'foo'], ['status' => [0, 1, 2, 3]]]);
        $command->insert('{{customer}}', $query)->execute();

        $this->assertEquals(
            2,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM {{customer}}
                SQL
            )->queryScalar(),
        );

        $record = $command->setSql(
            <<<SQL
            SELECT [[email]], [[name]], [[address]] FROM {{customer}}
            SQL
        )->queryAll();

        $this->assertSame(
            [
                ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'],
                ['email' => 't1@example.com', 'name' => 'test address', 'address' => 'test'],
            ],
            $record,
        );
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with wrong query object.
     *
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::invalidSelectColumns()
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testInsertSelectFailed(array|ExpressionInterface|string $invalidSelectColumns): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->select($invalidSelectColumns)->from('{{customer}}');
        $command = $db->createCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected select query object with enumerated (named) parameters');

        $command->insert('{{customer}}', $query)->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertToBlob(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->delete('type')->execute();
        $columns = [
            'int_col' => 1,
            'char_col' => 'test',
            'float_col' => 3.14,
            'bool_col' => true,
            'blob_col' => serialize(['test' => 'data', 'num' => 222]),
        ];
        $command->insert('type', $columns)->execute();
        $result = $command->setSql(
            <<<SQL
            SELECT [[blob_col]] FROM {{type}}
            SQL
        )->queryOne();

        $this->assertIsArray($result);

        $resultBlob = is_resource($result['blob_col']) ? stream_get_contents($result['blob_col']) : $result['blob_col'];

        $this->assertSame($columns['blob_col'], $resultBlob);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testIntegrityViolation(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(IntegrityException::class);

        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}}([[id]], [[description]]) VALUES (123, 'duplicate')
            SQL
        );
        $command->execute();
        $command->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testLastInsertId(): void
    {
        $db = $this->getConnectionWithData();

        $sql = <<<SQL
        INSERT INTO {{profile}}([[description]]) VALUES ('non duplicate')
        SQL;
        $db->createCommand($sql)->execute();

        $this->assertSame('3', $db->getLastInsertID());
    }

    /**
     * Verify that {{}} are not going to be replaced in parameters.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnectionWithData();

        $db
            ->createCommand()
            ->insert(
                '{{customer}}',
                ['name' => 'Some {{weird}} name', 'email' => 'test@example.com', 'address' => 'Some {{%weird}} address']
            )
            ->execute();

        if ($db->getName() === 'pgsql') {
            $customerId = $db->getLastInsertID('public.customer_id_seq');
        } else {
            $customerId = $db->getLastInsertID();
        }

        $customer = $db->createCommand(
            <<<SQL
            SELECT [[name]], [[email]], [[address]] FROM {{customer}} WHERE [[id]] = :id
            SQL,
            [':id' => $customerId]
        )->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{weird}} name', $customer['name']);
        $this->assertSame('Some {{%weird}} address', $customer['address']);

        $db
            ->createCommand()
            ->update(
                '{{customer}}',
                ['name' => 'Some {{updated}} name', 'address' => 'Some {{%updated}} address'],
                ['id' => $customerId]
            )
            ->execute();
        $customer = $db->createCommand(
            <<<SQL
            SELECT [[name]], [[email]], [[address]] FROM {{customer}} WHERE [[id]] = :id
            SQL,
            [':id' => $customerId]
        )->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{updated}} name', $customer['name']);
        $this->assertSame('Some {{%updated}} address', $customer['address']);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @todo check if this test is correct
     */
    public function testQuery(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        );

        $this->assertNull($command->getPdoStatement());

        $reader = $command->query();

        // check tests that the reader is a valid iterator
        if ($db->getName() !== 'sqlite' && $db->getName() !== 'pgsql' && $db->getName() !== 'sqlsrv') {
            $this->assertEquals(3, $reader->count());
        }

        $this->assertNotNull($command->getPdoStatement());
        $this->assertInstanceOf(DataReaderInterface::class, $reader);
        $this->assertIsInt($reader->count());

        foreach ($reader as $row) {
            $this->assertIsArray($row);
            $this->assertCount(6, $row);
        }

        $command = $db->createCommand('bad SQL');

        $this->expectException(Exception::class);

        $command->query();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryAll(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        );
        $rows = $command->queryAll();

        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);
        $this->assertIsArray($rows[0]);
        $this->assertCount(6, $rows[0]);

        $command = $db->createCommand('bad SQL');

        $this->expectException(Exception::class);

        $command->queryAll();
        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} where id = 100
            SQL
        );
        $rows = $command->queryAll();

        $this->assertIsArray($rows);
        $this->assertCount(0, $rows);
        $this->assertSame([], $rows);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryOne(): void
    {
        $db = $this->getConnectionWithData();

        $sql = <<<SQL
        SELECT * FROM {{customer}} ORDER BY [[id]]
        SQL;
        $row = $db->createCommand($sql)->queryOne();

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $command = $db->createCommand($sql);
        $command->prepare();
        $row = $command->queryOne();

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $sql = <<<SQL
        SELECT * FROM {{customer}} WHERE [[id]] = 10
        SQL;
        $command = $db->createCommand($sql);

        $this->assertNull($command->queryOne());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryCache(): void
    {
        $db = $this->getConnectionWithData();

        $query = $this->getQuery($db)->select(['name'])->from('customer');
        $command = $db->createCommand();
        $update = $command->setSql(
            <<<SQL
            UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id
            SQL
        );

        $this->assertSame('user1', $query->where(['id' => 1])->scalar(), 'Asserting initial value');

        /* No cache */
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();

        $this->assertSame(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'Query reflects DB changes when caching is disabled',
        );

        /* Connection cache */
        $db->cache(
            static function (ConnectionPDOInterface $db) use ($query, $update) {
                self::assertSame('user2', $query->where(['id' => 2])->scalar(), 'Asserting initial value for user #2');

                $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

                self::assertSame(
                    'user2',
                    $query->where(['id' => 2])->scalar(),
                    'Query does NOT reflect DB changes when wrapped in connection caching',
                );

                $db->noCache(
                    static function () use ($query) {
                        self::assertSame(
                            'user22',
                            $query->where(['id' => 2])->scalar(),
                            'Query reflects DB changes when wrapped in connection caching and noCache simultaneously',
                        );
                    }
                );

                self::assertSame(
                    'user2',
                    $query->where(['id' => 2])->scalar(),
                    'Cache does not get changes after getting newer data from DB in noCache block.',
                );
            },
            10,
        );

        $db->queryCacheEnable(false);

        $db->cache(
            static function () use ($query, $update) {
                self::assertSame(
                    'user22',
                    $query->where(['id' => 2])->scalar(),
                    'When cache is disabled for the whole connection, Query inside cache block does not get cached',
                );

                $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();

                self::assertSame('user2', $query->where(['id' => 2])->scalar());
            },
            10,
        );

        $db->queryCacheEnable(true);
        $query->cache();

        $this->assertSame('user11', $query->where(['id' => 1])->scalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();

        $this->assertSame(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'When both Connection and Query have cache enabled, we get cached value',
        );
        $this->assertSame(
            'user1',
            $query->noCache()->where(['id' => 1])->scalar(),
            'When Query has disabled cache, we get actual data',
        );

        $db->cache(
            static function () use ($query) {
                self::assertSame('user1', $query->noCache()->where(['id' => 1])->scalar());
                self::assertSame('user11', $query->cache()->where(['id' => 1])->scalar());
            },
            10,
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryColumn(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        );
        $rows = $command->queryColumn();

        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);
        $this->assertEquals('1', $rows[0]);

        $command = $db->createCommand('bad SQL');

        $this->expectException(Exception::class);

        $command->queryColumn();
        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} where id = 100
            SQL
        );
        $rows = $command->queryColumn();

        $this->assertIsArray($rows);
        $this->assertCount(0, $rows);
        $this->assertSame([], $rows);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryScalar(): void
    {
        $db = $this->getConnectionWithData();

        $sql = <<<SQL
        SELECT * FROM {{customer}} ORDER BY [[id]]
        SQL;

        $this->assertEquals(1, $db->createCommand($sql)->queryScalar());

        $sql = <<<SQL
        SELECT [[id]] FROM {{customer}} ORDER BY [[id]]
        SQL;
        $command = $db->createCommand($sql);
        $command->prepare();

        $this->assertEquals(1, $command->queryScalar());

        $command = $db->createCommand(
            <<<SQL
            SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10
            SQL
        );

        $this->assertFalse($command->queryScalar());
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

        if ($db->getSchema()->getTableSchema($toTableName) !== null) {
            $command->dropTable($toTableName)->execute();
        }

        $this->assertNotNull($db->getSchema()->getTableSchema($fromTableName));
        $this->assertNull($db->getSchema()->getTableSchema($toTableName));

        $command->renameTable($fromTableName, $toTableName)->execute();

        $this->assertNull($db->getSchema()->getTableSchema($fromTableName, true));
        $this->assertNotNull($db->getSchema()->getTableSchema($toTableName, true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testRetryHandler(): void
    {
        $db = $this->getConnectionwithData();

        $this->assertNull($db->getTransaction());

        $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}}([[description]]) VALUES('command retry')
            SQL
        )->execute();

        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command retry'
                SQL
            )->queryScalar()
        );

        $attempts = null;
        $hitHandler = false;
        $hitCatch = false;
        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}}([[id]], [[description]]) VALUES(1, 'command retry')
            SQL
        );

        Assert::invokeMethod(
            $command,
            'setRetryHandler',
            [static function ($exception, $attempt) use (&$attempts, &$hitHandler) {
                $attempts = $attempt;
                $hitHandler = true;

                return $attempt <= 2;
            }]
        );

        try {
            $command->execute();
        } catch (Exception $e) {
            $hitCatch = true;

            $this->assertInstanceOf(IntegrityException::class, $e);
        }

        $this->assertNull($db->getTransaction());
        $this->assertSame(3, $attempts);
        $this->assertTrue($hitHandler);
        $this->assertTrue($hitCatch);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testSerialize(): void
    {
        $db = $this->getConnection();

        $db->open();
        $serialized = serialize($db);

        $this->assertNotNull($db->getPDO());

        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ConnectionPDOInterface::class, $unserialized);
        $this->assertNull($unserialized->getPDO());
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123')->queryScalar());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testTransaction(): void
    {
        $db = $this->getConnectionWithData();

        $this->assertNull($db->getTransaction());

        $command = $db->createCommand();
        $command = $command->setSql(
            <<<SQL
            INSERT INTO {{profile}}([[description]]) VALUES('command transaction')
            SQL
        );

        Assert::invokeMethod($command, 'requireTransaction');

        $command->execute();

        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            1,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command transaction'
                SQL
            )->queryScalar(),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testTruncateTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $rows = $command->setSql(
            <<<SQL
            SELECT * FROM {{animal}}
            SQL
        )->queryAll();

        $this->assertCount(2, $rows);

        $command->truncateTable('animal')->execute();
        $rows = $command->setSql(
            <<<SQL
            SELECT * FROM {{animal}}
            SQL
        )->queryAll();

        $this->assertCount(0, $rows);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::update()
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        string $expected
    ): void {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $sql = $command->update($table, $columns, $conditions, $params)->getSql();

        $this->assertSame($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::upsert()
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnectionWithData();

        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $firstData);
        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $secondData);
    }
}
