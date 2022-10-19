<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\InvalidParamException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Data\DataReader;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\Support\Assert;

use function call_user_func_array;
use function date;
use function is_array;
use function rtrim;
use function setlocale;
use function time;

abstract class AbstractCommandTest extends TestCase
{
    public function testAddDropForeignKey(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_fk';
        $name = 'test_fk_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable(
            $tableName,
            [
                'int1' => 'integer not null unique',
                'int2' => 'integer not null unique',
                'int3' => 'integer not null unique',
                'int4' => 'integer not null unique',
                'unique ([[int1]], [[int2]])',
                'unique ([[int3]], [[int4]])',
            ],
        )->execute();

        $this->assertEmpty($schema->getTableForeignKeys($tableName, true));

        $db->createCommand()->addForeignKey($name, $tableName, ['int1'], $tableName, ['int3'])->execute();

        $this->assertSame(['int1'], $schema->getTableForeignKeys($tableName, true)[0]->getColumnNames());
        $this->assertSame(['int3'], $schema->getTableForeignKeys($tableName, true)[0]->getForeignColumnNames());

        $db->createCommand()->dropForeignKey($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableForeignKeys($tableName, true));

        $db->createCommand()->addForeignKey(
            $name,
            $tableName,
            ['int1', 'int2'],
            $tableName,
            ['int3', 'int4'],
        )->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableForeignKeys($tableName, true)[0]->getColumnNames());
        $this->assertSame(['int3', 'int4'], $schema->getTableForeignKeys($tableName, true)[0]->getForeignColumnNames());
    }

    public function testAddDropUnique(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_uq';
        $name = 'test_uq_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable(
            $tableName,
            ['int1' => 'integer not null', 'int2' => 'integer not null'],
        )->execute();

        $this->assertEmpty($schema->getTableUniques($tableName, true));

        $db->createCommand()->addUnique($name, $tableName, ['int1'])->execute();

        $this->assertSame(['int1'], $schema->getTableUniques($tableName, true)[0]->getColumnNames());

        $db->createCommand()->dropUnique($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableUniques($tableName, true));

        $db->createCommand()->addUnique($name, $tableName, ['int1', 'int2'])->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableUniques($tableName, true)[0]->getColumnNames());
    }

    public function testAlterTable(): void
    {
        $db = $this->getConnection();

        if ($db->getName() === 'sqlite') {
            $this->markTestSkipped('Sqlite does not support alterTable');
        }

        if ($db->getSchema()->getTableSchema('testAlterTable', true) !== null) {
            $db->createCommand()->dropTable('testAlterTable')->execute();
        }

        $db->createCommand()->createTable(
            'testAlterTable',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER],
        )->execute();
        $db->createCommand()->insert('testAlterTable', ['bar' => 1])->execute();
        $db->createCommand()->alterColumn('testAlterTable', 'bar', Schema::TYPE_STRING)->execute();
        $db->createCommand()->insert('testAlterTable', ['bar' => 'hello'])->execute();
        $records = $db->createCommand('SELECT [[id]], [[bar]] FROM {{testAlterTable}}')->queryAll();

        $this->assertSame([['id' => '1', 'bar' => '1'], ['id' => '2', 'bar' => 'hello']], $records);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsert(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            [
                ['t1@example.com', 't1', 't1 address'],
                ['t2@example.com', null, false],
            ]
        );

        $this->assertSame(2, $command->execute());

        $result = (new Query($db))
            ->select(['email', 'name', 'address'])
            ->from('{{customer}}')
            ->where(['=', '[[email]]', 't1@example.com'])
            ->one();

        $this->assertCount(3, $result);
        $this->assertSame(
            [
                'email' => 't1@example.com',
                'name' => 't1',
                'address' => 't1 address',
            ],
            $result,
        );

        $result = (new Query($db))
            ->select(['email', 'name', 'address'])
            ->from('{{customer}}')
            ->where(['=', '[[email]]', 't2@example.com'])
            ->one();

        $this->assertCount(3, $result);
        $this->assertSame(
            [
                'email' => 't2@example.com',
                'name' => null,
                'address' => '0',
            ],
            $result,
        );

        /**
         * @link https://github.com/yiisoft/yii2/issues/11693
         */
        $command = $db->createCommand();
        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            []
        );

        $this->assertSame(0, $command->execute());
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

        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        $db = $this->getConnection();

        try {
            /* This one sets decimal mark to comma sign */
            setlocale(LC_NUMERIC, 'ru_RU.utf8');
            $cols = ['int_col', 'char_col', 'float_col', 'bool_col'];
            $data = [
                [1, 'A', 9.735, true],
                [2, 'B', -2.123, false],
                [3, 'C', 2.123, false],
            ];

            /* clear data in "type" table */
            $db->createCommand()->delete('type')->execute();

            /* change, for point oracle. */
            if ($db->getDriver()->getDriverName() === 'oci') {
                $db->createCommand("ALTER SESSION SET NLS_NUMERIC_CHARACTERS='.,'")->execute();
            }

            /* batch insert on "type" table */
            $db->createCommand()->batchInsert('type', $cols, $data)->execute();
            $data = $db->createCommand(
                'SELECT [[int_col]], [[char_col]], [[float_col]], [[bool_col]] ' .
                'FROM {{type}} WHERE [[int_col]] IN (1,2,3) ORDER BY [[int_col]]'
            )->queryAll();

            $this->assertCount(3, $data);
            $this->assertSame('1', $data[0]['int_col']);
            $this->assertSame('2', $data[1]['int_col']);
            $this->assertSame('3', $data[2]['int_col']);

            /* rtrim because Postgres padds the column with whitespace */
            $this->assertSame('A', rtrim($data[0]['char_col']));
            $this->assertSame('B', rtrim($data[1]['char_col']));
            $this->assertSame('C', rtrim($data[2]['char_col']));
            $this->assertSame('9.735', $data[0]['float_col']);
            $this->assertSame('-2.123', $data[1]['float_col']);
            $this->assertSame('2.123', $data[2]['float_col']);
            $this->assertSame('1', $data[0]['bool_col']);
            Assert::assertIsOneOf($data[1]['bool_col'], ['0', false]);
            Assert::assertIsOneOf($data[2]['bool_col'], ['0', false]);
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
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            [
                ['t1@example.com', 'test_name', 'test_address'],
            ]
        );

        $this->assertSame(1, $command->execute());

        $result = (new Query($db))
            ->select(['email', 'name', 'address'])
            ->from('{{customer}}')
            ->where(['=', '[[email]]', 't1@example.com'])
            ->one();

        $this->assertCount(3, $result);
        $this->assertSame(
            [
                'email' => 't1@example.com',
                'name' => 'test_name',
                'address' => 'test_address',
            ],
            $result,
        );
    }

    public function testBatchInsertWithManyData(): void
    {
        $values = [];
        $attemptsInsertRows = 200;
        $db = $this->getConnection();

        $command = $db->createCommand();

        for ($i = 0; $i < $attemptsInsertRows; $i++) {
            $values[$i] = ['t' . $i . '@any.com', 't' . $i, 't' . $i . ' address'];
        }

        $command->batchInsert('{{customer}}', ['email', 'name', 'address'], $values);

        $this->assertSame($attemptsInsertRows, $command->execute());

        $insertedRowsCount = (new Query($db))->from('{{customer}}')->count();

        $this->assertGreaterThanOrEqual($attemptsInsertRows, $insertedRowsCount);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsertWithYield(): void
    {
        $rows = (static function () {
            foreach ([['test@email.com', 'test name', 'test address']] as $row) {
                yield $row;
            }
        })();

        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->batchInsert('{{customer}}', ['email', 'name', 'address'], $rows);

        $this->assertSame(1, $command->execute());
    }

    public function testBindValues(): void
    {
        $command = $this->getConnection()->createCommand();

        $values = ['int' => 1, 'string' => 'str'];
        $command->bindValues($values);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(2, $bindedValues);

        $param = new Param('str', 99);
        $command->bindValues(['param' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertSame($param, $bindedValues['param']);
        $this->assertNotEquals($param, $bindedValues['int']);

        // Replace test
        $command->bindValues(['int' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertSame($param, $bindedValues['int']);
    }

    public function testCreateDropIndex(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_idx';
        $name = 'test_idx_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable(
            $tableName,
            ['int1' => 'integer not null', 'int2' => 'integer not null'],
        )->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1'])->execute();

        $this->assertSame(['int1'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertFalse($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $db->createCommand()->dropIndex($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1', 'int2'])->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertFalse($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $db->createCommand()->dropIndex($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));
        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1'], QueryBuilder::INDEX_UNIQUE)->execute();

        $this->assertSame(['int1'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $db->createCommand()->dropIndex($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1', 'int2'], QueryBuilder::INDEX_UNIQUE)->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('testCreateTable') !== null) {
            $db->createCommand()->dropTable('testCreateTable')->execute();
        }

        $db->createCommand()->createTable(
            'testCreateTable',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER],
        )->execute();
        $db->createCommand()->insert('testCreateTable', ['bar' => 1])->execute();
        $records = $db->createCommand('SELECT [[id]], [[bar]] FROM {{testCreateTable}};')->queryAll();

        $this->assertSame([['id' => '1', 'bar' => '1']], $records);
    }

    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $subquery = (new Query($db))->select('bar')->from('testCreateViewTable')->where(['>', 'bar', '5']);

        if ($db->getSchema()->getTableSchema('testCreateView') !== null) {
            $db->createCommand()->dropView('testCreateView')->execute();
        }

        if ($db->getSchema()->getTableSchema('testCreateViewTable')) {
            $db->createCommand()->dropTable('testCreateViewTable')->execute();
        }

        $db->createCommand()->createTable(
            'testCreateViewTable',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER],
        )->execute();
        $db->createCommand()->insert('testCreateViewTable', ['bar' => 1])->execute();
        $db->createCommand()->insert('testCreateViewTable', ['bar' => 6])->execute();
        $db->createCommand()->createView('testCreateView', $subquery)->execute();
        $records = $db->createCommand('SELECT [[bar]] FROM {{testCreateView}};')->queryAll();

        $this->assertSame([['bar' => '6']], $records);
    }

    public function testColumnCase(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::CASE_NATURAL, $db->getActivePDO()->getAttribute(PDO::ATTR_CASE));

        $sql = 'SELECT [[customer_id]], [[total]] FROM {{order}}';
        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['CUSTOMER_ID']));
        $this->assertTrue(isset($rows[0]['TOTAL']));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testConstruct(): void
    {
        $db = $this->getConnection();

        /* null */
        $command = $db->createCommand();

        $this->assertEmpty($command->getSql());

        /* string */
        $sql = 'SELECT * FROM customer';
        $command = $db->createCommand($sql);

        $this->assertSame($sql, $command->getSql());
    }

    public function testDataReaderCreationException(): void
    {
        $db = $this->getConnection();

        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('The PDOStatement cannot be null.');

        $sql = 'SELECT * FROM {{customer}}';
        new DataReader($db->createCommand($sql));
    }

    public function testDataReaderRewindException(): void
    {
        $db = $this->getConnection();

        $this->expectException(InvalidCallException::class);
        $this->expectExceptionMessage('DataReader cannot rewind. It is a forward-only reader.');

        $sql = 'SELECT * FROM {{customer}}';
        $reader = $db->createCommand($sql)->query();
        $reader->next();
        $reader->rewind();
    }

    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $tableName = 'type';

        $this->assertNotNull($db->getSchema()->getTableSchema($tableName));

        $db->createCommand()->dropTable($tableName)->execute();

        $this->assertNull($db->getSchema()->getTableSchema($tableName));
    }

    public function testDropView(): void
    {
        $db = $this->getConnection();

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
        $db = $this->getConnection(true);

        $sql = 'INSERT INTO {{customer}}([[email]], [[name]], [[address]])'
            . ' VALUES (\'user4@example.com\', \'user4\', \'address4\')';

        $command = $db->createCommand($sql);

        $this->assertSame(1, $command->execute());

        $sql = 'SELECT COUNT(*) FROM {{customer}} WHERE [[name]] = \'user4\'';
        $command = $db->createCommand($sql);

        $this->assertSame('1', $command->queryScalar());

        $command = $db->createCommand('bad SQL');

        $this->expectException(Exception::class);
        $command->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetSetSql(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT * FROM customer';
        $command = $db->createCommand($sql);

        $this->assertSame($sql, $command->getSql());

        $sql2 = 'SELECT * FROM order';
        $command->setSql($sql2);

        $this->assertSame($sql2, $command->getSql());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsert(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{customer}}')->execute();
        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'],
        )->execute();

        $this->assertSame('1', $db->createCommand('SELECT COUNT(*) FROM {{customer}};')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryOne();

        $this->assertSame(['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'], $record);
    }

    public function testInsertEx(): void
    {
        $db = $this->getConnection();

        $result = $db->createCommand()->insertEx(
            'customer',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1',
            ]
        );

        $this->assertIsArray($result);
        $this->assertNotNull($result['id']);
    }

    public function testInsertExpression(): void
    {
        $expression = '';
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();

        $expression = match ($db->getName()) {
            'mysql' => "EXTRACT(YEAR FROM TIMESTAMP 'now')",
            'pgsql' => 'YEAR(NOW())',
            'sqlite' => "strftime('%Y')",
            'sqlsrv' => 'YEAR(GETDATE())',
            default => throw new InvalidArgumentException('Unsupported database type.'),
        };

        $command = $db->createCommand();
        $command->insert(
            '{{order_with_null_fk}}',
            ['created_at' => new Expression($expression), 'total' => 1],
        )->execute();

        $this->assertSame('1', $db->createCommand('SELECT COUNT(*) FROM {{order_with_null_fk}}')->queryScalar());

        $record = $db->createCommand('SELECT [[created_at]] FROM {{order_with_null_fk}}')->queryOne();

        $this->assertSame(['created_at' => date('Y')], $record);
    }

    public function testsInsertQueryAsColumnValue(): void
    {
        $time = time();

        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();
        $command = $db->createCommand();
        $command->insert('{{order}}', ['customer_id' => 1, 'created_at' => $time, 'total' => 42])->execute();

        if ($db->getName() === 'pgsql') {
            $orderId = $db->getLastInsertID('public.order_id_seq');
        } else {
            $orderId = $db->getLastInsertID();
        }

        $columnValueQuery = new Query($db);
        $columnValueQuery->select('created_at')->from('{{order}}')->where(['id' => $orderId]);
        $command = $db->createCommand();
        $command->insert(
            '{{order_with_null_fk}}',
            ['customer_id' => $orderId, 'created_at' => $columnValueQuery, 'total' => 42],
        )->execute();

        $this->assertSame(
            "$time",
            $db->createCommand(
                'SELECT [[created_at]] FROM {{order_with_null_fk}} WHERE [[customer_id]] = ' . $orderId,
            )->queryScalar()
        );

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();
        $db->createCommand('DELETE FROM {{order}} WHERE [[id]] = ' . $orderId)->execute();
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement.
     */
    public function testInsertSelect(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{customer}}')->execute();
        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'],
        )->execute();
        $query = (new Query($db))
            ->select(['{{customer}}.[[email]] as name', '[[name]] as email', '[[address]]'])
            ->from('{{customer}}')
            ->where(['and', ['<>', 'name', 'foo'], ['status' => [0, 1, 2, 3]]]);
        $command = $db->createCommand();
        $command->insert('{{customer}}', $query)->execute();

        $this->assertSame('2', $db->createCommand('SELECT COUNT(*) FROM {{customer}}')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryAll();

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
     */
    public function testInsertSelectAlias(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{customer}}')->execute();
        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'],
        )->execute();
        $query = (new Query($db))
            ->select(['email' => '{{customer}}.[[email]]', 'address' => 'name', 'name' => 'address'])
            ->from('{{customer}}')
            ->where(['and', ['<>', 'name', 'foo'], ['status' => [0, 1, 2, 3]]]);
        $command = $db->createCommand();
        $command->insert('{{customer}}', $query)->execute();

        $this->assertSame('2', $db->createCommand('SELECT COUNT(*) FROM {{customer}}')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryAll();

        $this->assertSame(
            [
                ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'],
                ['email' => 't1@example.com', 'name' => 'test address', 'address' => 'test'],
            ],
            $record,
        );
    }

    public function testInsertToBlob(): void
    {
        $db = $this->getConnection();

        $db->createCommand()->delete('type')->execute();

        $columns = [
            'int_col' => 1,
            'char_col' => 'test',
            'float_col' => 3.14,
            'bool_col' => true,
            'blob_col' => serialize(['test' => 'data', 'num' => 222]),
        ];
        $db->createCommand()->insert('type', $columns)->execute();
        $result = $db->createCommand('SELECT [[blob_col]] FROM {{type}}')->queryOne();

        $this->assertIsArray($result);

        $resultBlob = is_resource($result['blob_col']) ? stream_get_contents($result['blob_col']) : $result['blob_col'];

        $this->assertSame($columns['blob_col'], $resultBlob);
    }

    public function testIntegrityViolation(): void
    {
        $db = $this->getConnection();

        $this->expectException(IntegrityException::class);

        $sql = 'INSERT INTO {{profile}}([[id]], [[description]]) VALUES (123, \'duplicate\')';
        $command = $db->createCommand($sql);
        $command->execute();
        $command->execute();
    }

    public function testLastInsertId(): void
    {
        $db = $this->getConnection();

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate\')';
        $command = $db->createCommand($sql);
        $command->execute();

        $this->assertSame('3', $db->getLastInsertID());
    }

    public function testLastInsertIdException(): void
    {
        $db = $this->getConnection();

        $db->close();

        $this->expectException(InvalidCallException::class);

        $db->getLastInsertID();
    }

    /**
     * Verify that {{}} are not going to be replaced in parameters.
     */
    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnection();

        $db->createCommand()->insert(
            '{{customer}}',
            ['name' => 'Some {{weird}} name', 'email' => 'test@example.com', 'address' => 'Some {{%weird}} address'],
        )->execute();

        if ($db->getName() === 'pgsql') {
            $customerId = $db->getLastInsertID('public.customer_id_seq');
        } else {
            $customerId = $db->getLastInsertID();
        }

        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE id=' . $customerId)->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{weird}} name', $customer['name']);
        $this->assertSame('Some {{%weird}} address', $customer['address']);

        $db->createCommand()->update(
            '{{customer}}',
            ['name' => 'Some {{updated}} name', 'address' => 'Some {{%updated}} address'],
            ['id' => $customerId],
        )->execute();
        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE id=' . $customerId)->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{updated}} name', $customer['name']);
        $this->assertSame('Some {{%updated}} address', $customer['address']);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testPrepareCancel(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand('SELECT * FROM {{customer}}');

        $this->assertNull($command->getPdoStatement());

        $command->prepare();

        $this->assertNotNull($command->getPdoStatement());

        $command->cancel();

        $this->assertNull($command->getPdoStatement());
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function testQuery(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT * FROM {{customer}}';
        $reader = $db->createCommand($sql)->query();

        $this->assertInstanceOf(DataReaderInterface::class, $reader);

        /**
         * Next line is commented by reason:: For sqlite & pgsql result may be incorrect
         * $this->assertSame(3, $reader->count());
         */
        $this->assertIsInt($reader->count());

        foreach ($reader as $row) {
            $this->assertIsArray($row);
            $this->assertTrue((is_countable($row) ? count($row) : 0) >= 6);
        }

        $command = $db->createCommand('bad SQL');

        $this->expectException(Exception::class);

        $command->query();
    }

    public function testQueryAll(): void
    {
        $db = $this->getConnection(true);

        $rows = $db->createCommand('SELECT [[id]],[[name]] FROM {{customer}}')->queryAll();

        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);

        $row = $rows[2];

        $this->assertSame('3', $row['id']);
        $this->assertSame('user3', $row['name']);
        $this->assertTrue(is_array($rows) && count($rows) > 1 && (is_countable($rows[0]) ? count($rows[0]) : 0) === 2);

        $rows = $db->createCommand('SELECT * FROM {{customer}} WHERE [[id]] = 10')->queryAll();

        $this->assertSame([], $rows);
    }

    public function testQueryColumn(): void
    {
        $db = $this->getConnection(true);

        $sql = 'SELECT * FROM {{customer}}';
        $column = $db->createCommand($sql)->queryColumn();

        $this->assertSame(['1', '2', '3'], $column);
        $this->assertIsArray($column);

        $command = $db->createCommand('SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10');

        $this->assertEmpty($command->queryColumn());
    }

    public function testQueryOne(): void
    {
        $db = $this->getConnection(true);

        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';
        $row = $db->createCommand($sql)->queryOne();

        $this->assertIsArray($row);
        $this->assertSame('1', $row['id']);
        $this->assertSame('user1', $row['name']);

        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';
        $command = $db->createCommand($sql);
        $command->prepare();
        $row = $command->queryOne();

        $this->assertIsArray($row);
        $this->assertSame('1', $row['id']);
        $this->assertSame('user1', $row['name']);

        $sql = 'SELECT * FROM {{customer}} WHERE [[id]] = 10';
        $command = $db->createCommand($sql);

        $this->assertNull($command->queryOne());
    }

    public function testQueryScalar(): void
    {
        $db = $this->getConnection(true);

        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';

        $this->assertSame('1', $db->createCommand($sql)->queryScalar());

        $sql = 'SELECT [[id]] FROM {{customer}} ORDER BY [[id]]';
        $command = $db->createCommand($sql);
        $command->prepare();

        $this->assertSame('1', $command->queryScalar());

        $command = $db->createCommand('SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10');

        $this->assertFalse($command->queryScalar());
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection(true);

        $fromTableName = 'type';
        $toTableName = 'new_type';

        if ($db->getSchema()->getTableSchema($toTableName) !== null) {
            $db->createCommand()->dropTable($toTableName)->execute();
        }

        $this->assertNotNull($db->getSchema()->getTableSchema($fromTableName));
        $this->assertNull($db->getSchema()->getTableSchema($toTableName));

        $db->createCommand()->renameTable($fromTableName, $toTableName)->execute();

        $this->assertNull($db->getSchema()->getTableSchema($fromTableName, true));
        $this->assertNotNull($db->getSchema()->getTableSchema($toTableName, true));
    }

    public function testRetryHandler(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTransaction());

        $db->createCommand("INSERT INTO {{profile}}([[description]]) VALUES('command retry')")->execute();

        $this->assertNull($db->getTransaction());
        $this->assertSame(
            '1',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command retry'",
            )->queryScalar(),
        );

        $attempts = null;
        $hitHandler = false;
        $hitCatch = false;

        $command = $db->createCommand("INSERT INTO {{profile}}([[id]], [[description]]) VALUES(1, 'command retry')");

        Assert::invokeMethod(
            $command,
            'setRetryHandler',
            [
                static function ($exception, $attempt) use (&$attempts, &$hitHandler) {
                    $attempts = $attempt;
                    $hitHandler = true;

                    return $attempt <= 2;
                },
            ],
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

    public function testTransaction(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTransaction());

        $command = $db->createCommand("INSERT INTO {{profile}}([[description]]) VALUES('command transaction')");

        Assert::invokeMethod($command, 'requireTransaction');

        $command->execute();

        $this->assertNull($db->getTransaction());
        $this->assertSame(
            '1',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command transaction'",
            )->queryScalar()
        );
    }

    public function testTruncateTable(): void
    {
        $db = $this->getConnection();

        $rows = $db->createCommand('SELECT * FROM {{animal}}')->queryAll();

        $this->assertCount(2, $rows);

        $db->createCommand()->truncateTable('animal')->execute();
        $rows = $db->createCommand('SELECT * FROM {{animal}}')->queryAll();

        $this->assertCount(0, $rows);
    }

    protected function performAndCompareUpsertResult(ConnectionInterface $db, array $data): void
    {
        $params = $data['params'];
        $expected = $data['expected'] ?? $params[1];

        $command = $db->createCommand();
        call_user_func_array([$command, 'upsert'], $params);
        $command->execute();

        $actual = (new Query($db))
            ->select([
                'email',
                'address' => new Expression($this->upsertTestCharCast),
                'status',
            ])
            ->from('T_upsert')
            ->one();

        $this->assertSame($expected, $actual, $this->upsertTestCharCast);
    }
}
