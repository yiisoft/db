<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use PDO;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Data\DataReader;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;

use function call_user_func_array;
use function date;
use function is_array;
use function range;
use function rtrim;
use function setlocale;
use function time;

trait TestCommandTrait
{
    public function testConstruct(): void
    {
        $db = $this->getConnection();

        /* null */
        $command = $db->createCommand();

        $this->assertNull($command->getSql());

        /* string */
        $sql = 'SELECT * FROM customer';

        $command = $db->createCommand($sql);

        $this->assertEquals($sql, $command->getSql());
    }

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

    public function testQuery(): void
    {
        $db = $this->getConnection(true);

        /* query */
        $sql = 'SELECT * FROM {{customer}}';

        $reader = $db->createCommand($sql)->Query();

        $this->assertInstanceOf(DataReader::class, $reader);

        /* queryAll */
        $rows = $db->createCommand('SELECT * FROM {{customer}}')->queryAll();

        $this->assertCount(3, $rows);

        $row = $rows[2];

        $this->assertEquals(3, $row['id']);
        $this->assertEquals('user3', $row['name']);

        $rows = $db->createCommand('SELECT * FROM {{customer}} WHERE [[id]] = 10')->queryAll();

        $this->assertEquals([], $rows);

        /* queryOne */
        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';

        $row = $db->createCommand($sql)->queryOne();

        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';

        $command = $db->createCommand($sql);

        $command->prepare();

        $row = $command->queryOne();

        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $sql = 'SELECT * FROM {{customer}} WHERE [[id]] = 10';

        $command = $db->createCommand($sql);

        $this->assertFalse($command->queryOne());

        /* queryColumn */
        $sql = 'SELECT * FROM {{customer}}';

        $column = $db->createCommand($sql)->queryColumn();

        $this->assertEquals(range(1, 3), $column);

        $command = $db->createCommand('SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10');

        $this->assertEquals([], $command->queryColumn());

        /* queryScalar */
        $sql = 'SELECT * FROM {{customer}} ORDER BY [[id]]';

        $this->assertEquals($db->createCommand($sql)->queryScalar(), 1);

        $sql = 'SELECT [[id]] FROM {{customer}} ORDER BY [[id]]';

        $command = $db->createCommand($sql);

        $command->prepare();

        $this->assertEquals(1, $command->queryScalar());

        $command = $db->createCommand('SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10');

        $this->assertFalse($command->queryScalar());

        $command = $db->createCommand('bad SQL');

        $this->expectException(Exception::class);

        $command->Query();
    }

    public function testFetchMode(): void
    {
        $db = $this->getConnection();

        /* default: FETCH_ASSOC */
        $sql = 'SELECT * FROM {{customer}}';

        $command = $db->createCommand($sql);

        $result = $command->queryOne();

        $this->assertTrue(is_array($result) && isset($result['id']));

        /* FETCH_OBJ, customized via fetchMode property */
        $sql = 'SELECT * FROM {{customer}}';

        $command = $db->createCommand($sql);

        $command->setFetchMode(PDO::FETCH_OBJ);

        $result = $command->queryOne();

        $this->assertIsObject($result);

        /* FETCH_NUM, customized in query method */
        $sql = 'SELECT * FROM {{customer}}';

        $command = $db->createCommand($sql);

        $result = $command->queryOne(PDO::FETCH_NUM);

        $this->assertTrue(is_array($result) && isset($result[0]));
    }

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

        /**
         * {@see https://github.com/yiisoft/yii2/issues/11693}
         */
        $command = $this->getConnection()->createCommand();

        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            []
        );

        $this->assertEquals(0, $command->execute());
    }

    public function testBatchInsertWithYield(): void
    {
        $rows = (static function () {
            if (false) {
                yield [];
            }
        })();

        $command = $this->getConnection()->createCommand();

        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            $rows
        );

        $this->assertEquals(0, $command->execute());
    }

    /**
     * Test batch insert with different data types.
     *
     * Ensure double is inserted with `.` decimal separator.
     *
     * {@see https://github.com/yiisoft/yii2/issues/6526}
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
            if ($db->getDriverName() === 'oci') {
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
        } catch (Exception $e) {
            setlocale(LC_NUMERIC, $locale);

            throw $e;
        } catch (Throwable $e) {
            setlocale(LC_NUMERIC, $locale);

            throw $e;
        }

        setlocale(LC_NUMERIC, $locale);
    }

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

    /**
     * verify that {{}} are not going to be replaced in parameters.
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

        if ($db->getDriverName() === 'pgsql') {
            $customerId = $db->getLastInsertID('public.customer_id_seq');
        } else {
            $customerId = $db->getLastInsertID();
        }

        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE id=' . $customerId)->queryOne();

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
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();

        switch ($db->getDriverName()) {
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

        $this->assertEquals([
            'created_at' => date('Y'),
        ], $record);
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

        if ($db->getDriverName() === 'pgsql') {
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

        $this->assertEquals([
            ['id' => 1, 'bar' => 1],
        ], $records);
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

        $db->createCommand()->createIndex($name, $tableName, ['int1'], true)->execute();

        $this->assertEquals(['int1'], $schema->getTableIndexes($tableName, true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());

        $db->createCommand()->dropIndex($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $db->createCommand()->createIndex($name, $tableName, ['int1', 'int2'], true)->execute();

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

        $this->assertEquals(3, $db->getSchema()->getLastInsertID());
    }

    public function testQueryCache(): void
    {
        $db = $this->getConnection(true);

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

        $this->queryCache->setEnable(false);

        $db->cache(function () use ($command, $update) {
            $this->assertEquals('user22', $command->bindValue(':id', 2)->queryScalar());
            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();
            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());
        }, 10);

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

        $this->assertEquals(PDO::CASE_NATURAL, $db->getSlavePdo()->getAttribute(PDO::ATTR_CASE));

        $sql = 'SELECT [[customer_id]], [[total]] FROM {{order}}';

        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

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
        return [
            'issue11242' => [
                'type',
                ['int_col', 'float_col', 'char_col'],
                [['', '', 'Kyiv {{city}}, Ukraine']],
                /**
                 * {@see https://github.com/yiisoft/yii2/issues/11242}
                 *
                 * Make sure curly bracelets (`{{..}}`) in values will not be escaped
                 */
                'expected' => 'INSERT INTO `type` (`int_col`, `float_col`, `char_col`)'
                    . " VALUES (NULL, NULL, 'Kyiv {{city}}, Ukraine')",
            ],
            'wrongBehavior' => [
                '{{%type}}',
                ['{{%type}}.[[int_col]]', '[[float_col]]', 'char_col'],
                [['', '', 'Kyiv {{city}}, Ukraine']],
                /**
                 * Test covers potentially wrong behavior and marks it as expected!.
                 *
                 * In case table name or table column is passed with curly or square bracelets, QueryBuilder can not
                 * determine the table schema and typecast values properly.
                 * TODO: make it work. Impossible without BC breaking for public methods.
                 */
                'expected' => 'INSERT INTO `type` (`type`.`int_col`, `float_col`, `char_col`)'
                    . " VALUES ('', '', 'Kyiv {{city}}, Ukraine')",
            ],
            'batchInsert binds params from expression' => [
                '{{%type}}',
                ['int_col'],
                /**
                 * This example is completely useless. This feature of batchInsert is intended to be used with complex
                 * expression objects, such as JsonExpression.
                 */
                [[new Expression(':qp1', [':qp1' => 42])]],
                'expected' => 'INSERT INTO `type` (`int_col`) VALUES (:qp1)',
                'expectedParams' => [':qp1' => 42],
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

        if ($db->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Sqlite does not support alterTable');
        }

        if ($db->getSchema()->getTableSchema('testAlterTable') !== null) {
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
}
