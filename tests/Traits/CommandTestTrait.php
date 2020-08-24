<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Traits;

use Yiisoft\Db\Data\DataReader;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;

trait CommandTestTrait
{
    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    public function testConstruct(): void
    {
        $db = $this->getConnection(false);

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
        $db = $this->getConnection(false);

        $sql = 'SELECT * FROM customer';

        $command = $db->createCommand($sql);

        $this->assertEquals($sql, $command->getSql());

        $sql2 = 'SELECT * FROM order';

        $command->setSql($sql2);

        $this->assertEquals($sql2, $command->getSql());
    }

    public function testPrepareCancel(): void
    {
        $db = $this->getConnection(false);

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

        $sql = 'INSERT INTO {{customer}}([[email]], [[name]], [[address]]) VALUES (\'user4@example.com\', \'user4\', \'address4\')';

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

        /** query */
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

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider paramsNonWhereProvider
     *
     * @param string $sql
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getConnection();

        $db->createCommand()->insert(
            'customer',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1'
            ]
        )->execute();

        $params = [
            ':email' => 'testParams@example.com',
            ':len'   => 5,
        ];

        $command = $db->createCommand($sql, $params);

        $this->assertEquals('Params', $command->queryScalar());
    }

    public function testFetchMode(): void
    {
        $db = $this->getConnection();

        /* default: FETCH_ASSOC */
        $sql = 'SELECT * FROM {{customer}}';

        $command = $db->createCommand($sql);

        $result = $command->queryOne();

        $this->assertTrue(\is_array($result) && isset($result['id']));

        /* FETCH_OBJ, customized via fetchMode property */
        $sql = 'SELECT * FROM {{customer}}';

        $command = $db->createCommand($sql);

        $command->setFetchMode(\PDO::FETCH_OBJ);

        $result = $command->queryOne();

        $this->assertIsObject($result);

        /* FETCH_NUM, customized in query method */
        $sql = 'SELECT * FROM {{customer}}';

        $command = $db->createCommand($sql);

        $result = $command->queryOne(\PDO::FETCH_NUM);

        $this->assertTrue(\is_array($result) && isset($result[0]));
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
        $rows = call_user_func(function () {
            if (false) {
                yield [];
            }
        });

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
     * https://github.com/yiisoft/yii2/issues/6526
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

            /* batch insert on "type" table */
            $db->createCommand()->batchInsert('type', $cols, $data)->execute();

            $data = $db->createCommand(
                'SELECT int_col, char_col, float_col, bool_col FROM {{type}} WHERE [[int_col]] IN (1,2,3)
                ORDER BY [[int_col]];'
            )->queryAll();

            $this->assertEquals(3, \count($data));
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
        } catch (\Throwable $e) {
            setlocale(LC_NUMERIC, $locale);

            throw $e;
        }

        setlocale(LC_NUMERIC, $locale);
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     *
     * @dataProvider batchInsertSqlProvider
     *
     * @param mixed $table
     * @param mixed $columns
     * @param mixed $values
     * @param mixed $expected
     * @param array $expectedParams
     */
    public function testBatchInsertSQL($table, $columns, $values, $expected, array $expectedParams = []): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $command->batchInsert($table, $columns, $values);

        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());
    }

    public function testInsert(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{customer}}')->execute();

        $command = $db->createCommand();

        $command->insert(
            '{{customer}}',
            [
                'email'   => 't1@example.com',
                'name'    => 'test',
                'address' => 'test address',
            ]
        )->execute();

        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{customer}};')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryOne();

        $this->assertEquals([
            'email'   => 't1@example.com',
            'name'    => 'test',
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
                'name'    => 'Some {{weird}} name',
                'email'   => 'test@example.com',
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
                'name'    => 'Some {{updated}} name',
                'address' => 'Some {{%updated}} address',
            ],
            ['id' => $customerId]
        )->execute();

        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE id=' . $customerId)->queryOne();

        $this->assertEquals('Some {{updated}} name', $customer['name']);
        $this->assertEquals('Some {{%updated}} address', $customer['address']);
    }
}
