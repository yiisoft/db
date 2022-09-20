<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport;

use PDO;
use Throwable;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\InvalidParamException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\Query\Data\DataReader;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\TestSupport\Helper\DbHelper;

use function call_user_func_array;
use function date;
use function is_array;
use function range;
use function rtrim;
use function setlocale;
use function time;

trait TestCommandTrait
{
    /**
     * @throws Exception|InvalidConfigException
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
        $this->assertEquals($sql, $command->getSql());
    }

    /**
     * @throws Exception|InvalidConfigException
     */
    public function testGetSetSql(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT * FROM customer';
        $command = $db->createCommand($sql);
        $this->assertEquals($sql, $command->getSql());

        $sql2 = 'SELECT * FROM order';
        $command->setSql($sql2);
        $this->assertEquals($sql2, $command->getSql());
    }

    /**
     * @throws Exception|InvalidConfigException
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
    public function testExecute(): void
    {
        $db = $this->getConnection(true);

        $sql = 'INSERT INTO {{customer}}([[email]], [[name]], [[address]])'
            . ' VALUES (\'user4@example.com\', \'user4\', \'address4\')';

        $command = $db->createCommand($sql);
        $this->assertEquals(1, $command->execute());

        $sql = 'SELECT COUNT(*) FROM {{customer}} WHERE [[name]] = \'user4\'';
        $command = $db->createCommand($sql);
        $this->assertEquals(1, $command->queryScalar());

        $command = $db->createCommand('bad SQL');
        $this->expectException(Exception::class);
        $command->execute();
    }

    public function testDataReaderCreationException(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('The PDOStatement cannot be null.');

        $sql = 'SELECT * FROM {{customer}}';
        new DataReader($db->createCommand($sql));
    }

    public function testDataReaderRewindException(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(InvalidCallException::class);
        $this->expectExceptionMessage('DataReader cannot rewind. It is a forward-only reader.');

        $sql = 'SELECT * FROM {{customer}}';
        $reader = $db->createCommand($sql)->query();
        $reader->next();
        $reader->rewind();
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function testQuery(): void
    {
        $db = $this->getConnection(true);

        $sql = 'SELECT * FROM {{customer}}';
        $reader = $db->createCommand($sql)->query();
        $this->assertInstanceOf(DataReaderInterface::class, $reader);

        // Next line is commented by reason:: For sqlite & pgsql result may be incorrect
        // $this->assertEquals(3, $reader->count());
        $this->assertIsInt($reader->count());
        foreach ($reader as $row) {
            $this->assertIsArray($row);
            $this->assertTrue(count($row) >= 6);
        }

        $command = $db->createCommand('bad SQL');
        $this->expectException(Exception::class);
        $command->query();
    }

    public function testQyeryScalar(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';
        $this->assertEquals($db->createCommand($sql)->queryScalar(), 1);

        $sql = 'SELECT [[id]] FROM {{customer}} ORDER BY [[id]]';
        $command = $db->createCommand($sql);

        $command->prepare();
        $this->assertEquals(1, $command->queryScalar());

        $command = $db->createCommand('SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10');
        $this->assertFalse($command->queryScalar());
    }

    public function testQueryOne(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';
        $row = $db->createCommand($sql)->queryOne();
        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';
        $command = $db->createCommand($sql);
        $command->prepare();
        $row = $command->queryOne();

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $sql = 'SELECT * FROM {{customer}} WHERE [[id]] = 10';
        $command = $db->createCommand($sql);
        $this->assertNull($command->queryOne());
    }

    public function testQueryColumn(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT * FROM {{customer}}';
        $column = $db->createCommand($sql)->queryColumn();
        $this->assertEquals(range(1, 3), $column);
        $this->assertIsArray($column);

        $command = $db->createCommand('SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10');
        $this->assertEmpty($command->queryColumn());
    }

    public function testQueryAll(): void
    {
        $db = $this->getConnection();

        $rows = $db->createCommand('SELECT [[id]],[[name]] FROM {{customer}}')->queryAll();
        /** @psalm-suppress RedundantCondition */
        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);

        $row = $rows[2];
        $this->assertEquals(3, $row['id']);
        $this->assertEquals('user3', $row['name']);
        $this->assertTrue(is_array($rows) && count($rows)>1 && count($rows[0]) === 2);

        $rows = $db->createCommand('SELECT * FROM {{customer}} WHERE [[id]] = 10')->queryAll();
        $this->assertEquals([], $rows);
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
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
        $this->assertEquals(2, $command->execute());
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
        $this->assertEquals(0, $command->execute());
    }

    public function testBatchInsertWithManyData(): void
    {
        $attemptsInsertRows = 200;
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        for ($i = 0; $i < $attemptsInsertRows; $i++) {
            $values[$i] = ['t' . $i . '@any.com', 't' . $i, 't' . $i . ' address'];
        }

        $command->batchInsert('{{customer}}', ['email', 'name', 'address'], $values);

        $this->assertEquals($attemptsInsertRows, $command->execute());

        $insertedRowsCount = (new Query($db))->from('{{customer}}')->count();
        $this->assertGreaterThanOrEqual($attemptsInsertRows, $insertedRowsCount);
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
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
        $this->assertEquals(1, $command->execute());

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

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function testBatchInsertWithYield(): void
    {
        $rows = (static function () {
            foreach ([['test@email.com', 'test name', 'test address']] as $row) {
                yield $row;
            }
        })();

        $command = $this->getConnection()->createCommand();
        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            $rows
        );
        $this->assertEquals(1, $command->execute());
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

        $db = $this->getConnection(true);

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
            $this->assertEquals(1, $data[0]['int_col']);
            $this->assertEquals(2, $data[1]['int_col']);
            $this->assertEquals(3, $data[2]['int_col']);

            /* rtrim because Postgres padds the column with whitespace */
            $this->assertEquals('A', rtrim($data[0]['char_col']));
            $this->assertEquals('B', rtrim($data[1]['char_col']));
            $this->assertEquals('C', rtrim($data[2]['char_col']));
            $this->assertEquals('9.735', $data[0]['float_col']);
            $this->assertEquals('-2.123', $data[1]['float_col']);
            $this->assertEquals('2.123', $data[2]['float_col']);
            $this->assertEquals('1', $data[0]['bool_col']);
            $this->assertIsOneOf($data[1]['bool_col'], ['0', false]);
            $this->assertIsOneOf($data[2]['bool_col'], ['0', false]);
        } catch (Exception|Throwable $e) {
            setlocale(LC_NUMERIC, $locale);
            throw $e;
        }

        setlocale(LC_NUMERIC, $locale);
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function testInsert(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{customer}}')->execute();
        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ]
        )->execute();
        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{customer}};')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryOne();
        $this->assertEquals([
            'email' => 't1@example.com',
            'name' => 'test',
            'address' => 'test address',
        ], $record);
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

    /**
     * Verify that {{}} are not going to be replaced in parameters.
     */
    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnection(true);

        $db->createCommand()->insert(
            '{{customer}}',
            [
                'name' => 'Some {{weird}} name',
                'email' => 'test@example.com',
                'address' => 'Some {{%weird}} address',
            ]
        )->execute();

        if ($db->getDriver()->getDriverName() === 'pgsql') {
            $customerId = $db->getLastInsertID('public.customer_id_seq');
        } else {
            $customerId = $db->getLastInsertID();
        }

        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE id=' . $customerId)->queryOne();
        $this->assertIsArray($customer);
        $this->assertEquals('Some {{weird}} name', $customer['name']);
        $this->assertEquals('Some {{%weird}} address', $customer['address']);

        $db->createCommand()->update(
            '{{customer}}',
            [
                'name' => 'Some {{updated}} name',
                'address' => 'Some {{%updated}} address',
            ],
            ['id' => $customerId]
        )->execute();
        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE id=' . $customerId)->queryOne();
        $this->assertIsArray($customer);
        $this->assertEquals('Some {{updated}} name', $customer['name']);
        $this->assertEquals('Some {{%updated}} address', $customer['address']);
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
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ]
        )->execute();
        $query = new Query($db);
        $query->select(
            [
                '{{customer}}.[[email]] as name',
                '[[name]] as email',
                '[[address]]',
            ]
        )
            ->from('{{customer}}')
            ->where([
                'and',
                ['<>', 'name', 'foo'],
                ['status' => [0, 1, 2, 3]],
            ]);
        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            $query
        )->execute();
        $this->assertEquals(2, $db->createCommand('SELECT COUNT(*) FROM {{customer}}')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryAll();
        $this->assertEquals([
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ],
            [
                'email' => 'test',
                'name' => 't1@example.com',
                'address' => 'test address',
            ],
        ], $record);
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
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ]
        )->execute();
        $query = new Query($db);
        $query->select(
            [
                'email' => '{{customer}}.[[email]]',
                'address' => 'name',
                'name' => 'address',
            ]
        )
            ->from('{{customer}}')
            ->where([
                'and',
                ['<>', 'name', 'foo'],
                ['status' => [0, 1, 2, 3]],
            ]);
        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            $query
        )->execute();
        $this->assertEquals(2, $db->createCommand('SELECT COUNT(*) FROM {{customer}}')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryAll();
        $this->assertEquals([
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ],
            [
                'email' => 't1@example.com',
                'name' => 'test address',
                'address' => 'test',
            ],
        ], $record);
    }

    public function testInsertExpression(): void
    {
        $expression = '';
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();
        switch ($db->getDriver()->getDriverName()) {
            case 'pgsql':
                $expression = "EXTRACT(YEAR FROM TIMESTAMP 'now')";
                break;
            case 'mysql':
                $expression = 'YEAR(NOW())';
                break;
            case 'sqlite':
                $expression = "strftime('%Y')";
                break;
            case 'sqlsrv':
                $expression = 'YEAR(GETDATE())';
        }
        $command = $db->createCommand();
        $command->insert(
            '{{order_with_null_fk}}',
            [
                'created_at' => new Expression($expression),
                'total' => 1,
            ]
        )->execute();
        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{order_with_null_fk}}')->queryScalar());

        $record = $db->createCommand('SELECT [[created_at]] FROM {{order_with_null_fk}}')->queryOne();
        $this->assertEquals(['created_at' => date('Y')], $record);
    }

    public function testsInsertQueryAsColumnValue(): void
    {
        $time = time();
        $db = $this->getConnection(true);

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();
        $command = $db->createCommand();
        $command->insert('{{order}}', [
            'customer_id' => 1,
            'created_at' => $time,
            'total' => 42,
        ])->execute();

        if ($db->getDriver()->getDriverName() === 'pgsql') {
            $orderId = $db->getLastInsertID('public.order_id_seq');
        } else {
            $orderId = $db->getLastInsertID();
        }

        $columnValueQuery = new Query($db);
        $columnValueQuery->select('created_at')->from('{{order}}')->where(['id' => $orderId]);
        $command = $db->createCommand();
        $command->insert(
            '{{order_with_null_fk}}',
            [
                'customer_id' => $orderId,
                'created_at' => $columnValueQuery,
                'total' => 42,
            ]
        )->execute();
        $this->assertEquals(
            $time,
            $db->createCommand(
                'SELECT [[created_at]] FROM {{order_with_null_fk}} WHERE [[customer_id]] = ' . $orderId
            )->queryScalar()
        );

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();
        $db->createCommand('DELETE FROM {{order}} WHERE [[id]] = ' . $orderId)->execute();
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('testCreateTable') !== null) {
            $db->createCommand()->dropTable('testCreateTable')->execute();
        }

        $db->createCommand()->createTable(
            'testCreateTable',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER]
        )->execute();
        $db->createCommand()->insert('testCreateTable', ['bar' => 1])->execute();
        $records = $db->createCommand('SELECT [[id]], [[bar]] FROM {{testCreateTable}};')->queryAll();
        $this->assertEquals([['id' => 1, 'bar' => 1]], $records);
    }

    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $tableName = 'type';
        $this->assertNotNull($db->getSchema()->getTableSchema($tableName));

        $db->createCommand()->dropTable($tableName)->execute();
        $this->assertNull($db->getSchema()->getTableSchema($tableName));
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
        $this->assertEquals($expected, $actual, $this->upsertTestCharCast);
    }

    public function testAddDropForeignKey(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_fk';
        $name = 'test_fk_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer not null unique',
            'int2' => 'integer not null unique',
            'int3' => 'integer not null unique',
            'int4' => 'integer not null unique',
            'unique ([[int1]], [[int2]])',
            'unique ([[int3]], [[int4]])',
        ])->execute();
        $this->assertEmpty($schema->getTableForeignKeys($tableName, true));

        $db->createCommand()->addForeignKey($name, $tableName, ['int1'], $tableName, ['int3'])->execute();
        $this->assertEquals(['int1'], $schema->getTableForeignKeys($tableName, true)[0]->getColumnNames());
        $this->assertEquals(['int3'], $schema->getTableForeignKeys($tableName, true)[0]->getForeignColumnNames());

        $db->createCommand()->dropForeignKey($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableForeignKeys($tableName, true));

        $db->createCommand()->addForeignKey(
            $name,
            $tableName,
            ['int1', 'int2'],
            $tableName,
            ['int3', 'int4']
        )->execute();
        $this->assertEquals(
            ['int1', 'int2'],
            $schema->getTableForeignKeys($tableName, true)[0]->getColumnNames()
        );
        $this->assertEquals(
            ['int3', 'int4'],
            $schema->getTableForeignKeys($tableName, true)[0]->getForeignColumnNames()
        );
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

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer not null',
            'int2' => 'integer not null',
        ])->execute();
        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1'])->execute();
        $this->assertEquals(['int1'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertFalse($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $db->createCommand()->dropIndex($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1', 'int2'])->execute();
        $this->assertEquals(['int1', 'int2'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertFalse($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $db->createCommand()->dropIndex($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableIndexes($tableName, true));
        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1'], QueryBuilder::INDEX_UNIQUE)->execute();
        $this->assertEquals(['int1'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $db->createCommand()->dropIndex($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1', 'int2'], QueryBuilder::INDEX_UNIQUE)->execute();
        $this->assertEquals(['int1', 'int2'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());
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

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer not null',
            'int2' => 'integer not null',
        ])->execute();
        $this->assertEmpty($schema->getTableUniques($tableName, true));

        $db->createCommand()->addUnique($name, $tableName, ['int1'])->execute();
        $this->assertEquals(['int1'], $schema->getTableUniques($tableName, true)[0]->getColumnNames());

        $db->createCommand()->dropUnique($name, $tableName)->execute();
        $this->assertEmpty($schema->getTableUniques($tableName, true));

        $db->createCommand()->addUnique($name, $tableName, ['int1', 'int2'])->execute();
        $this->assertEquals(['int1', 'int2'], $schema->getTableUniques($tableName, true)[0]->getColumnNames());
    }

    public function testBindValues(): void
    {
        $command = $this->getConnection()->createCommand();

        $values = [
            'int' => 1,
            'string' => 'str',
        ];
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
        $this->assertEquals($param, $bindedValues['param']);
        $this->assertNotEquals($param, $bindedValues['int']);

        // Replace test
        $command->bindValues(['int' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertEquals($param, $bindedValues['int']);
    }

    public function testIntegrityViolation(): void
    {
        $this->expectException(IntegrityException::class);

        $db = $this->getConnection();

        $sql = 'INSERT INTO {{profile}}([[id]], [[description]]) VALUES (123, \'duplicate\')';
        $command = $db->createCommand($sql);
        $command->execute();
        $command->execute();
    }

    public function testLastInsertId(): void
    {
        $db = $this->getConnection(true);

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate\')';
        $command = $db->createCommand($sql);
        $command->execute();
        $this->assertEquals(3, $db->getLastInsertID());
    }

    public function testLastInsertIdException(): void
    {
        $db = $this->getConnection();
        $db->close();

        $this->expectException(InvalidCallException::class);
        $db->getLastInsertID();
    }

    public function testQueryCache(): void
    {
        $db = $this->getConnection(true);

        /** @psalm-suppress PossiblyNullReference */
        $this->queryCache->setEnable(true);
        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');
        $this->assertEquals('user1', $command->bindValue(':id', 1)->queryScalar());

        $update = $db->createCommand('UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id');
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();
        $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());

        $db->cache(function (ConnectionInterface $db) use ($command, $update) {
            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());

            $db->noCache(function () use ($command) {
                $this->assertEquals('user22', $command->bindValue(':id', 2)->queryScalar());
            });

            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());
        }, 10);

        /** @psalm-suppress PossiblyNullReference */
        $this->queryCache->setEnable(false);

        $db->cache(function () use ($command, $update) {
            $this->assertEquals('user22', $command->bindValue(':id', 2)->queryScalar());
            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();
            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());
        }, 10);

        /** @psalm-suppress PossiblyNullReference */
        $this->queryCache->setEnable(true);
        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id')->cache();
        $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();
        $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());
        $this->assertEquals('user1', $command->noCache()->bindValue(':id', 1)->queryScalar());

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

        $db->cache(function () use ($command) {
            $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());
            $this->assertEquals('user1', $command->noCache()->bindValue(':id', 1)->queryScalar());
        }, 10);
    }

    public function testColumnCase(): void
    {
        $db = $this->getConnection();

        $this->assertEquals(PDO::CASE_NATURAL, $db->getActivePDO()->getAttribute(PDO::ATTR_CASE));

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

    public function testTransaction(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTransaction());

        $command = $db->createCommand("INSERT INTO {{profile}}([[description]]) VALUES('command transaction')");
        $this->invokeMethod($command, 'requireTransaction');
        $command->execute();
        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            1,
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command transaction'"
            )->queryScalar()
        );
    }

    public function testRetryHandler(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTransaction());

        $db->createCommand("INSERT INTO {{profile}}([[description]]) VALUES('command retry')")->execute();
        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            1,
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command retry'"
            )->queryScalar()
        );

        $attempts = null;
        $hitHandler = false;
        $hitCatch = false;

        $command = $db->createCommand(
            "INSERT INTO {{profile}}([[id]], [[description]]) VALUES(1, 'command retry')"
        );
        $this->invokeMethod(
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

    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $subquery = (new Query($db))
            ->select('bar')
            ->from('testCreateViewTable')
            ->where(['>', 'bar', '5']);

        if ($db->getSchema()->getTableSchema('testCreateView') !== null) {
            $db->createCommand()->dropView('testCreateView')->execute();
        }

        if ($db->getSchema()->getTableSchema('testCreateViewTable')) {
            $db->createCommand()->dropTable('testCreateViewTable')->execute();
        }

        $db->createCommand()->createTable('testCreateViewTable', [
            'id' => Schema::TYPE_PK,
            'bar' => Schema::TYPE_INTEGER,
        ])->execute();
        $db->createCommand()->insert('testCreateViewTable', ['bar' => 1])->execute();
        $db->createCommand()->insert('testCreateViewTable', ['bar' => 6])->execute();
        $db->createCommand()->createView('testCreateView', $subquery)->execute();
        $records = $db->createCommand('SELECT [[bar]] FROM {{testCreateView}};')->queryAll();
        $this->assertEquals([['bar' => 6]], $records);
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

    public function batchInsertSqlProviderTrait(): array
    {
        $db = $this->getConnection();

        return [
            'multirow' => [
                'type',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'values' => [
                    ['0', '0.0', 'test string', true,],
                    [false, 0, 'test string2', false,],
                ],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:qp0, :qp1, :qp2, :qp3), (:qp4, :qp5, :qp6, :qp7)',
                    $db->getDriver()->getDriverName(),
                ),
                'expectedParams' => [
                    ':qp0' => 0,
                    ':qp1' => 0.0,
                    ':qp2' => 'test string',
                    ':qp3' => true,
                    ':qp4' => 0,
                    ':qp5' => 0.0,
                    ':qp6' => 'test string2',
                    ':qp7' => false,
                ],
                2,
            ],
            'issue11242' => [
                'type',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'values' => [[1.0, 1.1, 'Kyiv {{city}}, Ukraine', true]],
                /**
                 * {@see https://github.com/yiisoft/yii2/issues/11242}
                 *
                 * Make sure curly bracelets (`{{..}}`) in values will not be escaped
                 */
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:qp0, :qp1, :qp2, :qp3)',
                    $db->getDriver()->getDriverName(),
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 1.1,
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => true,
                ],
            ],
            'wrongBehavior' => [
                '{{%type}}',
                ['{{%type}}.[[int_col]]', '[[float_col]]', 'char_col', 'bool_col'],
                'values' => [['0', '0.0', 'Kyiv {{city}}, Ukraine', false]],
                /**
                 * Test covers potentially wrong behavior and marks it as expected!.
                 *
                 * In case table name or table column is passed with curly or square bracelets, QueryBuilder can not
                 * determine the table schema and typecast values properly.
                 * TODO: make it work. Impossible without BC breaking for public methods.
                 */
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[type]] ([[type]].[[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:qp0, :qp1, :qp2, :qp3)',
                    $db->getDriver()->getDriverName(),
                ),
                'expectedParams' => [
                    ':qp0' => '0',
                    ':qp1' => '0.0',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                ],
            ],
            'batchInsert binds params from expression' => [
                '{{%type}}',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                /**
                 * This example is completely useless. This feature of batchInsert is intended to be used with complex
                 * expression objects, such as JsonExpression.
                 */
                'values' => [[new Expression(':exp1', [':exp1' => 42]), 1, 'test', false]],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:exp1, :qp1, :qp2, :qp3)',
                    $db->getDriver()->getDriverName(),
                ),
                'expectedParams' => [
                    ':exp1' => 42,
                    ':qp1' => 1.0,
                    ':qp2' => 'test',
                    ':qp3' => false,
                ],
            ],
        ];
    }

    public function bindParamsNonWhereProviderTrait(): array
    {
        return [
            ['SELECT SUBSTR(name, :len) FROM {{customer}} WHERE [[email]] = :email GROUP BY SUBSTR(name, :len)'],
            ['SELECT SUBSTR(name, :len) FROM {{customer}} WHERE [[email]] = :email ORDER BY SUBSTR(name, :len)'],
            ['SELECT SUBSTR(name, :len) FROM {{customer}} WHERE [[email]] = :email'],
        ];
    }

    public function getRawSqlProviderTrait(): array
    {
        return [
            [
                'SELECT * FROM customer WHERE id = :id',
                [':id' => 1],
                'SELECT * FROM customer WHERE id = 1',
            ],
            [
                'SELECT * FROM customer WHERE id = :id',
                ['id' => 1],
                'SELECT * FROM customer WHERE id = 1',
            ],
            [
                'SELECT * FROM customer WHERE id = :id',
                ['id' => null],
                'SELECT * FROM customer WHERE id = NULL',
            ],
            [
                'SELECT * FROM customer WHERE id = :base OR id = :basePrefix',
                [
                    'base' => 1,
                    'basePrefix' => 2,
                ],
                'SELECT * FROM customer WHERE id = 1 OR id = 2',
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/9268}
             */
            [
                'SELECT * FROM customer WHERE active = :active',
                [':active' => false],
                'SELECT * FROM customer WHERE active = FALSE',
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/15122}
             */
            [
                'SELECT * FROM customer WHERE id IN (:ids)',
                [':ids' => new Expression(implode(', ', [1, 2]))],
                'SELECT * FROM customer WHERE id IN (1, 2)',
            ],
        ];
    }

    public function invalidSelectColumnsProviderTrait(): array
    {
        return [
            [[]],
            ['*'],
            [['*']],
        ];
    }

    public function upsertProviderTrait(): array
    {
        return [
            'regular values' => [
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Earth',
                            'status' => 3,
                        ],
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Universe',
                            'status' => 1,
                        ],
                    ],
                ],
            ],
            'regular values with update part' => [
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Earth',
                            'status' => 3,
                        ],
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Universe',
                            'status' => 1,
                        ],
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                    'expected' => [
                        'email' => 'foo@example.com',
                        'address' => 'Moon',
                        'status' => 2,
                    ],
                ],
            ],
            'regular values without update part' => [
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Earth',
                            'status' => 3,
                        ],
                        false,
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Universe',
                            'status' => 1,
                        ],
                        false,
                    ],
                    'expected' => [
                        'email' => 'foo@example.com',
                        'address' => 'Earth',
                        'status' => 3,
                    ],
                ],
            ],
            'query' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query($this->getConnection()))
                            ->select([
                                'email',
                                'address',
                                'status' => new Expression('1'),
                            ])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => 1,
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query($this->getConnection()))
                            ->select([
                                'email',
                                'address',
                                'status' => new Expression('2'),
                            ])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => 2,
                    ],
                ],
            ],
            'query with update part' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query($this->getConnection()))
                            ->select([
                                'email',
                                'address',
                                'status' => new Expression('1'),
                            ])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => 1,
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query($this->getConnection()))
                            ->select([
                                'email',
                                'address',
                                'status' => new Expression('3'),
                            ])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'Moon',
                        'status' => 2,
                    ],
                ],
            ],
            'query without update part' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query($this->getConnection()))
                            ->select([
                                'email',
                                'address',
                                'status' => new Expression('1'),
                            ])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => 1,
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query($this->getConnection()))
                            ->select([
                                'email',
                                'address',
                                'status' => new Expression('2'),
                            ])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => 1,
                    ],
                ],
            ],
        ];
    }

    public function testAlterTable(): void
    {
        $db = $this->getConnection();

        if ($db->getDriver()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Sqlite does not support alterTable');
        }

        if ($db->getSchema()->getTableSchema('testAlterTable', true) !== null) {
            $db->createCommand()->dropTable('testAlterTable')->execute();
        }

        $db->createCommand()->createTable(
            'testAlterTable',
            [
                'id' => Schema::TYPE_PK,
                'bar' => Schema::TYPE_INTEGER,
            ]
        )->execute();

        $db->createCommand()->insert('testAlterTable', ['bar' => 1])->execute();
        $db->createCommand()->alterColumn('testAlterTable', 'bar', Schema::TYPE_STRING)->execute();
        $db->createCommand()->insert('testAlterTable', ['bar' => 'hello'])->execute();

        $records = $db->createCommand('SELECT [[id]], [[bar]] FROM {{testAlterTable}}')->queryAll();
        $this->assertEquals([
            ['id' => 1, 'bar' => 1],
            ['id' => 2, 'bar' => 'hello'],
        ], $records);
    }

    public function testInsertToBlob(): void
    {
        $db = $this->getConnection(true);

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

        $this->assertEquals($columns['blob_col'], $resultBlob);
    }
}
