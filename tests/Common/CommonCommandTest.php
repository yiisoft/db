<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Throwable;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\AbstractCommandTest;
use Yiisoft\Db\Tests\Support\Assert;

use function setlocale;

abstract class CommonCommandTest extends AbstractCommandTest
{
    public function testAlterTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('testAlterTable', true) !== null) {
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
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsertSQL(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1,
        string $fixture = 'type'
    ): void {
        $db = $this->getConnection($fixture);

        $command = $db->createCommand();
        $command->batchInsert($table, $columns, $values);
        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());

        $command->execute();

        $this->assertEquals($insertedRow, (new Query($db))->from($table)->count());
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

        $db = $this->getConnection('type');

        $command = $db->createCommand();

        try {
            /* This one sets decimal mark to comma sign */
            setlocale(LC_NUMERIC, 'ru_RU.utf8');

            $cols = ['int_col', 'char_col', 'float_col', 'bool_col'];
            $data = [[1, 'A', 9.735, true], [2, 'B', -2.123, false], [3, 'C', 2.123, false]];

            /* clear data in "type" table */
            $command->delete('type')->execute();

            /* change, for point oracle. */
            if ($db->getName() === 'oci') {
                $command->setSql(
                    <<<SQL
                    ALTER SESSION SET NLS_NUMERIC_CHARACTERS='.,'
                    SQL
                )->execute();
            }

            /* batch insert on "type" table */
            $command->batchInsert('type', $cols, $data)->execute();
            $data = $command->setSql(
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

    public function testBatchInsertFailsOld(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();
        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            [['t1@example.com', 'test_name', 'test_address']],
        );

        $this->assertSame(1, $command->execute());

        $result = (new Query($db))
            ->select(['email', 'name', 'address'])
            ->from('{{customer}}')
            ->where(['=', '[[email]]', 't1@example.com'])
            ->one();

        $this->assertCount(3, $result);
        $this->assertSame(['email' => 't1@example.com', 'name' => 'test_name', 'address' => 'test_address'], $result);
    }

    public function testBatchInsertWithManyData(): void
    {
        $db = $this->getConnection('customer');

        $values = [];
        $attemptsInsertRows = 200;
        $command = $db->createCommand();

        for ($i = 0; $i < $attemptsInsertRows; $i++) {
            $values[$i] = ['t' . $i . '@any.com', 't' . $i, 't' . $i . ' address'];
        }

        $command->batchInsert('{{customer}}', ['email', 'name', 'address'], $values);

        $this->assertSame($attemptsInsertRows, $command->execute());

        $insertedRowsCount = (new Query($db))->from('{{customer}}')->count();

        $this->assertGreaterThanOrEqual($attemptsInsertRows, $insertedRowsCount);
    }

    public function testBatchInsertWithYield(): void
    {
        $db = $this->getConnection('customer');

        $rows = (
            static function () {
                yield ['test@email.com', 'test name', 'test address'];
            }
        )();
        $command = $db->createCommand();
        $command->batchInsert('{{customer}}', ['email', 'name', 'address'], $rows);

        $this->assertSame(1, $command->execute());
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('testCreateTable', true) !== null) {
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

    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $subQuery = (new Query($db))->select('bar')->from('testCreateViewTable')->where(['>', 'bar', '5']);

        if ($schema->getTableSchema('testCreateView') !== null) {
            $command->dropView('testCreateView')->execute();
        }

        if ($schema->getTableSchema('testCreateViewTable')) {
            $command->dropTable('testCreateViewTable')->execute();
        }

        $command->createTable(
            'testCreateViewTable',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER],
        )->execute();
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
        $db = $this->getConnection('customer');

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

    public function testDropView(): void
    {
        $db = $this->getConnection('animal');

        /* since it already exists in the fixtures */
        $viewName = 'animal_view';

        $schema = $db->getSchema();

        $this->assertNotNull($schema->getTableSchema($viewName));

        $db->createCommand()->dropView($viewName)->execute();

        $this->assertNull($schema->getTableSchema($viewName));
    }

    public function testExecute(): void
    {
        $db = $this->getConnection('customer');

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
            'sqlsrv' => 'SQLSTATE[42000]: [Microsoft]',
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        $command->execute();
    }

    public function testIntegrityViolation(): void
    {
        $db = $this->getConnection('profile');

        $this->expectException(IntegrityException::class);

        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}}([[id]], [[description]]) VALUES (123, 'duplicate')
            SQL
        );
        $command->execute();
        $command->execute();
    }

    public function testLastInsertId(): void
    {
        $db = $this->getConnection('profile');

        $command = $db->createCommand();

        $sql = <<<SQL
        INSERT INTO {{profile}}([[description]]) VALUES ('non duplicate')
        SQL;
        $command->setSql($sql)->execute();

        $this->assertSame('3', $db->getLastInsertID());
    }

    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            ['name' => 'Some {{weird}} name', 'email' => 'test@example.com', 'address' => 'Some {{%weird}} address']
        )->execute();

        if ($db->getName() === 'pgsql') {
            $customerId = $db->getLastInsertID('public.customer_id_seq');
        } else {
            $customerId = $db->getLastInsertID();
        }

        $customer = $command->setSql(
            <<<SQL
            SELECT [[name]], [[email]], [[address]] FROM {{customer}} WHERE [[id]]=:id
            SQL,
        )->bindValues([':id' => $customerId])->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{weird}} name', $customer['name']);
        $this->assertSame('Some {{%weird}} address', $customer['address']);

        $command->update(
            '{{customer}}',
            ['name' => 'Some {{updated}} name', 'address' => 'Some {{%updated}} address'],
            ['id' => $customerId]
        )->execute();
        $customer = $command->setSql(
            <<<SQL
            SELECT [[name]], [[email]], [[address]] FROM {{customer}} WHERE [[id]] = :id
            SQL
        )->bindValues([':id' => $customerId])->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{updated}} name', $customer['name']);
        $this->assertSame('Some {{%updated}} address', $customer['address']);
    }

    public function testQuery(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();

        $command->setSql(
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

    public function testQueryAll(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();

        $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        );
        $rows = $command->queryAll();

        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);
        $this->assertIsArray($rows[0]);
        $this->assertCount(6, $rows[0]);

        $command->setSql('bad SQL');

        $this->expectException(Exception::class);

        $command->queryAll();
        $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}} where id = 100
            SQL
        );
        $rows = $command->queryAll();

        $this->assertIsArray($rows);
        $this->assertCount(0, $rows);
        $this->assertSame([], $rows);
    }

    public function testQueryOne(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT * FROM {{customer}} ORDER BY [[id]]
        SQL;
        $row = $command->setSql($sql)->queryOne();

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $command->setSql($sql)->prepare();
        $row = $command->queryOne();

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('user1', $row['name']);

        $sql = <<<SQL
        SELECT * FROM {{customer}} WHERE [[id]] = 10
        SQL;
        $command = $command->setSql($sql);

        $this->assertNull($command->queryOne());
    }

    public function testQueryCache(): void
    {
        $db = $this->getConnection('customer');

        $query = (new Query($db))->select(['name'])->from('customer');
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

    public function testQueryColumn(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        );
        $rows = $command->queryColumn();

        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);
        $this->assertEquals('1', $rows[0]);

        $command->setSql('bad SQL');

        $this->expectException(Exception::class);

        $command->queryColumn();
        $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}} where id = 100
            SQL
        );
        $rows = $command->queryColumn();

        $this->assertIsArray($rows);
        $this->assertCount(0, $rows);
        $this->assertSame([], $rows);
    }

    public function testQueryScalar(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT * FROM {{customer}} ORDER BY [[id]]
        SQL;

        $this->assertEquals(1, $command->setSql($sql)->queryScalar());

        $sql = <<<SQL
        SELECT [[id]] FROM {{customer}} ORDER BY [[id]]
        SQL;
        $command->setSql($sql)->prepare();

        $this->assertEquals(1, $command->queryScalar());

        $command = $command->setSql(
            <<<SQL
            SELECT [[id]] FROM {{customer}} WHERE [[id]] = 10
            SQL
        );

        $this->assertFalse($command->queryScalar());
    }

    public function testRetryHandler(): void
    {
        $db = $this->getConnection('profile');

        $command = $db->createCommand();

        $this->assertNull($db->getTransaction());

        $command->setSql(
            <<<SQL
            INSERT INTO {{profile}}([[description]]) VALUES('command retry')
            SQL
        )->execute();

        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            1,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command retry'
                SQL
            )->queryScalar()
        );

        $attempts = null;
        $hitHandler = false;
        $hitCatch = false;
        $command->setSql(
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

    public function testTransaction(): void
    {
        $db = $this->getConnection('profile');

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

    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        string $expected
    ): void {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->update($table, $columns, $conditions, $params)->getSql();

        $this->assertSame($expected, $sql);
    }

    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection('customer', 't_upsert');

        if (version_compare($db->getServerVersion(), '3.8.3', '<')) {
            $this->markTestSkipped('SQLite < 3.8.3 does not support "WITH" keyword.');
        }

        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());

        $this->performAndCompareUpsertResult($db, $firstData);

        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());

        $this->performAndCompareUpsertResult($db, $secondData);
    }
}
