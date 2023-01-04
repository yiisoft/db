<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

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
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\AbstractCommandTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Transaction\TransactionInterface;

use function call_user_func_array;
use function is_string;
use function setlocale;

abstract class CommonCommandTest extends AbstractCommandTest
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_ck}}') !== null) {
            $command->dropTable('{{test_ck}}')->execute();
        }

        $command->createTable('{{test_ck}}', ['int1' => 'integer'])->execute();

        $this->assertEmpty($schema->getTableChecks('{{test_ck}}', true));

        $command->addCheck('{{test_ck_constraint}}', '{{test_ck}}', '{{int1}} > 1')->execute();

        $this->assertMatchesRegularExpression(
            '/^.*int1.*>.*1.*$/',
            $schema->getTableChecks('{{test_ck}}', true)[0]->getExpression()
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->addColumn('{{customer}}', '{{city}}', Schema::TYPE_STRING)->execute();

        $this->assertTrue($db->getTableSchema('{{customer}}')->getColumn('city') !== null);
        $this->assertSame(Schema::TYPE_STRING, $db->getTableSchema('{{customer}}')->getColumn('city')->getType());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection(true);

        $tableName = '{{customer}}';
        $tableComment = 'Primary key.';

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $command->addCommentOnColumn($tableName, 'id', $tableComment)->execute();
        $commentOnColumn = $schema->getTableSchema($tableName)->getColumn('id')->getComment();

        $this->assertSame($tableComment, $commentOnColumn);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection(true);

        $tableName = '{{customer}}';
        $commentText = 'Customer table.';

        $command = $db->createCommand();
        $command->addCommentOnTable($tableName, $commentText)->execute();
        $commentOnTable = $db->getSchema()->getTableSchema($tableName, true)->getComment();

        $this->assertSame($commentText, $commentOnTable);
    }

    public function testResetSequenceSql(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->assertEmpty($command->getRawSql());
        $command->resetSequence('{{%customer}}');
        $this->assertNotEmpty($command->getRawSql());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_def}}') !== null) {
            $command->dropTable('{{test_def}}')->execute();
        }

        $command->createTable('{{test_def}}', ['int1' => Schema::TYPE_INTEGER])->execute();

        $this->assertEmpty($schema->getTableDefaultValues('{{test_def}}', true));

        $command->addDefaultValue('{{test_def_constraint}}', '{{test_def}}', 'int1', 41)->execute();

        $this->assertMatchesRegularExpression(
            '/^.*41.*$/',
            $schema->getTableDefaultValues('{{test_def}}', true)[0]->getValue(),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::addForeignKey()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddForeignKey(
        string $name,
        string $tableName,
        array|string $column1,
        array|string $column2,
        string $expectedName,
    ): void {
        $db = $this->getConnection();

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
            ],
        )->execute();

        $this->assertEmpty($schema->getTableForeignKeys($tableName, true));

        $command->addForeignKey($name, $tableName, $column1, $tableName, $column2)->execute();

        $this->assertSame($expectedName, $schema->getTableForeignKeys($tableName, true)[0]->getName());

        if (is_string($column1)) {
            $column1 = [$column1];
        }

        $this->assertSame($column1, $schema->getTableForeignKeys($tableName, true)[0]->getColumnNames());

        if (is_string($column2)) {
            $column2 = [$column2];
        }

        $this->assertSame($column2, $schema->getTableForeignKeys($tableName, true)[0]->getForeignColumnNames());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::addPrimaryKey()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddPrimaryKey(string $name, string $tableName, array|string $column): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName, true));

        $db->createCommand()->addPrimaryKey($name, $tableName, $column)->execute();

        if (is_string($column)) {
            $column = [$column];
        }

        $this->assertSame($column, $schema->getTablePrimaryKey($tableName, true)->getColumnNames());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::addUnique()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testAddUnique(string $name, string $tableName, array|string $column): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableUniques($tableName, true));

        $command->addUnique($name, $tableName, $column)->execute();

        if (is_string($column)) {
            $column = [$column];
        }

        $this->assertSame($column, $schema->getTableUniques($tableName, true)[0]->getColumnNames());
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::batchInsert()
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->batchInsert($table, $columns, $values);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());

        $command->prepare(false);
        $command->execute();

        $this->assertEquals($insertedRow, (new Query($db))->from($table)->count());
    }

    /**
     * Test batch insert with different data types.
     *
     * Ensure double is inserted with `.` decimal separator.
     *
     * @link https://github.com/yiisoft/yii2/issues/6526
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsertDataTypesLocale(): void
    {
        $locale = setlocale(LC_NUMERIC, 0);

        if ($locale === false) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        $db = $this->getConnection(true);

        $command = $db->createCommand();

        try {
            /* This one sets decimal mark to comma sign */
            setlocale(LC_NUMERIC, 'ru_RU.utf8');

            $cols = ['int_col', 'char_col', 'float_col', 'bool_col'];
            $data = [[1, 'A', 9.735, true], [2, 'B', -2.123, false], [3, 'C', 2.123, false]];

            /* clear data in "type" table */
            $command->delete('{{type}}')->execute();

            /* change, for point oracle. */
            if ($db->getName() === 'oci') {
                $command->setSql(
                    <<<SQL
                    ALTER SESSION SET NLS_NUMERIC_CHARACTERS='.,'
                    SQL
                )->execute();
            }

            /* batch insert on "type" table */
            $command->batchInsert('{{type}}', $cols, $data)->execute();
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
            [['t1@example.com', 'test_name', 'test_address']],
        );

        $this->assertSame(1, $command->execute());

        $result = (new Query($db))
            ->select(['email', 'name', 'address'])
            ->from('{{customer}}')
            ->where(['=', '{{email}}', 't1@example.com'])
            ->one();

        $this->assertCount(3, $result);
        $this->assertSame(['email' => 't1@example.com', 'name' => 'test_name', 'address' => 'test_address'], $result);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsertWithManyData(): void
    {
        $db = $this->getConnection(true);

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBatchInsertWithYield(): void
    {
        $db = $this->getConnection(true);

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
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::createIndex()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testCreateIndex(
        string $name,
        string $tableName,
        array|string $column,
        string|null $indexType,
        string|null $indexMethod,
    ): void {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableIndexes($tableName, true));

        $command->createIndex($name, $tableName, $column, $indexType, $indexMethod)->execute();

        if (is_string($column)) {
            $column = [$column];
        }

        $this->assertSame($column, $schema->getTableIndexes($tableName, true)[0]->getColumnNames());

        if ($indexType === 'UNIQUE') {
            $this->assertTrue($schema->getTableIndexes($tableName, true)[0]->isUnique());
        } else {
            $this->assertFalse($schema->getTableIndexes($tableName, true)[0]->isUnique());
        }
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
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{testCreateTable}}', true) !== null) {
            $command->dropTable('{{testCreateTable}}')->execute();
        }

        $command->createTable(
            '{{testCreateTable}}',
            ['[[id]]' => Schema::TYPE_PK, '[[bar]]' => Schema::TYPE_INTEGER],
        )->execute();
        $command->insert('{{testCreateTable}}', ['[[bar]]' => 1])->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[id]], [[bar]] FROM [[testCreateTable]];
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
        $schema = $db->getSchema();
        $subQuery = (new Query($db))->select('{{bar}}')->from('{{testCreateViewTable}}')->where(['>', 'bar', '5']);

        if ($schema->getTableSchema('{{testCreateView}}') !== null) {
            $command->dropView('{{testCreateView}}')->execute();
        }

        if ($schema->getTableSchema('{{testCreateViewTable}}')) {
            $command->dropTable('{{testCreateViewTable}}')->execute();
        }

        $command->createTable(
            '{{testCreateViewTable}}',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER],
        )->execute();
        $command->insert('{{testCreateViewTable}}', ['bar' => 1])->execute();
        $command->insert('{{testCreateViewTable}}', ['bar' => 6])->execute();
        $command->createView('{{testCreateView}}', $subQuery)->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[bar]] FROM {{testCreateView}};
            SQL
        )->queryAll();

        $this->assertEquals([['bar' => 6]], $records);

        $command->dropView('{{testCreateView}}')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDataReaderRewindException(): void
    {
        $db = $this->getConnection(true);

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDelete(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{customer}}', ['id' => 2])->execute();
        $chekSql = <<<SQL
        SELECT COUNT([[id]]) FROM [[customer]]
        SQL;
        $command->setSql($chekSql);

        $this->assertSame('2', $command->queryScalar());

        $command->delete('{{customer}}', ['id' => 3])->execute();
        $command->setSql($chekSql);

        $this->assertSame('1', $command->queryScalar());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_ck}}') !== null) {
            $command->dropTable('{{test_ck}}')->execute();
        }

        $command->createTable('{{test_ck}}', ['int1' => 'integer'])->execute();

        $this->assertEmpty($schema->getTableChecks('{{test_ck}}', true));

        $command->addCheck('{{test_ck_constraint}}', '{{test_ck}}', '[[int1]] > 1')->execute();

        $this->assertMatchesRegularExpression(
            '/^.*int1.*>.*1.*$/',
            $schema->getTableChecks('{{test_ck}}', true)[0]->getExpression(),
        );

        $command->dropCheck('{{test_ck_constraint}}', '{{test_ck}}')->execute();

        $this->assertEmpty($schema->getTableChecks('{{test_ck}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{testDropColumn}}', true) !== null) {
            $command->dropTable('{{testDropColumn}}')->execute();
        }

        $command->createTable(
            '{{testDropColumn}}',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER, 'baz' => Schema::TYPE_INTEGER],
        )->execute();
        $command->dropColumn('{{testDropColumn}}', 'bar')->execute();

        $this->assertArrayNotHasKey('bar', $schema->getTableSchema('{{testDropColumn}}')->getColumns());
        $this->assertArrayHasKey('baz', $schema->getTableSchema('{{testDropColumn}}')->getColumns());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection(true);

        $tableName = '{{customer}}';
        $tableComment = 'Primary key.';

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $command->addCommentOnColumn($tableName, 'id', $tableComment)->execute();
        $commentOnColumn = $schema->getTableSchema($tableName)->getColumn('id')->getComment();

        $this->assertSame($tableComment, $commentOnColumn);

        $command->dropCommentFromColumn($tableName, 'id')->execute();
        $commentOnColumn = $schema->getTableSchema($tableName)->getColumn('id')->getComment();

        $this->assertEmpty($commentOnColumn);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection(true);

        $tableName = '{{customer}}';
        $commentText = 'Customer table.';

        $command = $db->createCommand();
        $command->addCommentOnTable($tableName, $commentText)->execute();
        $commentOnTable = $db->getSchema()->getTableSchema($tableName, true)->getComment();

        $this->assertSame($commentText, $commentOnTable);

        $command->dropCommentFromTable($tableName)->execute();
        $commentOnTable = $db->getSchema()->getTableSchema($tableName, true)->getComment();

        $this->assertEmpty($commentOnTable);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_def}}') !== null) {
            $command->dropTable('{{test_def}}')->execute();
        }

        $command->createTable('{{test_def}}', ['int1' => 'integer'])->execute();

        $this->assertEmpty($schema->getTableDefaultValues('{{test_def}}', true));

        $command->addDefaultValue('{{test_def_constraint}}', '{{test_def}}', 'int1', 41)->execute();

        $this->assertMatchesRegularExpression(
            '/^.*41.*$/',
            $schema->getTableDefaultValues('{{test_def}}', true)[0]->getValue(),
        );

        $command->dropDefaultValue('{{test_def_constraint}}', '{{test_def}}')->execute();

        $this->assertEmpty($schema->getTableDefaultValues('{{test_def}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropForeignKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_fk}}') !== null) {
            $command->dropTable('{{test_fk}}')->execute();
        }

        $command->createTable('{{test_fk}}', ['id' => Schema::TYPE_PK, 'int1' => 'integer'])->execute();

        $this->assertEmpty($schema->getTableForeignKeys('{{test_fk}}', true));

        $command->addForeignKey('{{test_fk_constraint}}', '{{test_fk}}', 'int1', '{{test_fk}}', 'id')->execute();

        $this->assertNotEmpty($schema->getTableForeignKeys('{{test_fk}}', true));

        $command->dropForeignKey('{{test_fk_constraint}}', '{{test_fk}}')->execute();

        $this->assertEmpty($schema->getTableForeignKeys('{{test_fk}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_idx}}') !== null) {
            $command->dropTable('{{test_idx}}')->execute();
        }

        $command->createTable('{{test_idx}}', ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableIndexes('{[test_idx}}', true));

        $command->createIndex('{{test_idx_constraint}}', '{{test_idx}}', ['int1', 'int2'], 'UNIQUE')->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableIndexes('{{test_idx}}', true)[0]->getColumnNames());
        $this->assertTrue($schema->getTableIndexes('{{test_idx}}', true)[0]->isUnique());

        $command->dropIndex('{{test_idx_constraint}}', '{{test_idx}}')->execute();

        $this->assertEmpty($schema->getTableIndexes('{{test_idx}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_pk}}') !== null) {
            $command->dropTable('{{test_pk}}')->execute();
        }

        $command->createTable('{{test_pk}}', ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableSchema('{{test_pk}}', true)->getPrimaryKey());

        $command->addPrimaryKey('{{test_pk_constraint}}', '{{test_pk}}', ['int1', 'int2'])->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableSchema('{{test_pk}}', true)->getColumnNames());

        $command->dropPrimaryKey('{{test_pk_constraint}}', '{{test_pk}}')->execute();

        $this->assertEmpty($schema->getTableSchema('{{test_pk}}', true)->getPrimaryKey());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{testDropTable}}') !== null) {
            $command->dropTable('{{testDropTable}}')->execute();
        }

        $command->createTable('{{testDropTable}}', ['id' => Schema::TYPE_PK, 'foo' => 'integer'])->execute();

        $this->assertNotNull($schema->getTableSchema('{{testDropTable}}', true));

        $command->dropTable('{{testDropTable}}')->execute();

        $this->assertNull($schema->getTableSchema('{{testDropTable}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_uq}}') !== null) {
            $command->dropTable('{{test_uq}}')->execute();
        }

        $command->createTable('{{test_uq}}', ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableUniques('{{test_uq}}', true));

        $command->addUnique('{{test_uq_constraint}}', '{{test_uq}}', ['int1'])->execute();

        $this->assertSame(['int1'], $schema->getTableUniques('{{test_uq}}', true)[0]->getColumnNames());

        $command->dropUnique('{{test_uq_constraint}}', '{{test_uq}}')->execute();

        $this->assertEmpty($schema->getTableUniques('{{test_uq}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropView(): void
    {
        $db = $this->getConnection(true);

        /* since it already exists in the fixtures */
        $viewName = '{{animal_view}}';

        $schema = $db->getSchema();

        $this->assertNotNull($schema->getTableSchema($viewName));

        $db->createCommand()->dropView($viewName)->execute();

        $this->assertNull($schema->getTableSchema($viewName));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testExecute(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            INSERT INTO [[customer]] ([[email]], [[name]], [[address]]) VALUES ('user4@example.com', 'user4', 'address4')
            SQL
        );

        $this->assertSame(1, $command->execute());

        $command = $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM [[customer]] WHERE [[name]] = 'user4'
            SQL
        );

        $this->assertEquals(1, $command->queryScalar());

        $command->setSql('bad SQL');
        $message = match ($db->getName()) {
            'pgsql' => 'SQLSTATE[42601]',
            'sqlite', 'oci' => 'SQLSTATE[HY000]',
            default => 'SQLSTATE[42000]',
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
    public function testExecuteWithoutSql(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $result = $command->setSql('')->execute();

        $this->assertSame(0, $result);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testExecuteWithTransaction(): void
    {
        $db = $this->getConnection(true);

        $this->assertNull($db->getTransaction());

        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}} ([[description]]) VALUES('command transaction 1')
            SQL,
        );

        Assert::invokeMethod($command, 'requireTransaction');

        $command->execute();

        $this->assertNull($db->getTransaction());

        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command transaction 1'
                SQL,
            )->queryScalar(),
        );

        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}} ([[description]]) VALUES('command transaction 2')
            SQL,
        );

        Assert::invokeMethod($command, 'requireTransaction', [TransactionInterface::READ_UNCOMMITTED]);

        $command->execute();

        $this->assertNull($db->getTransaction());

        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command transaction 2'
                SQL,
            )->queryScalar(),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsert(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{customer}}')->execute();
        $command->insert(
            '{{customer}}',
            ['[[email]]' => 't1@example.com', '[[name]]' => 'test', '[[address]]' => 'test address']
        )->execute();

        $this->assertEquals(
            1,
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
        )->queryOne();

        $this->assertSame(['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'], $record);
    }

    /**
     * @throws Exception
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertEx(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $expected = match ($db->getName()) {
            'pgsql' => ['id' => 4],
            default => ['id' => '4'],
        };

        $this->assertSame(
            $expected,
            $command->insertEx('{{customer}}', ['name' => 'test_1', 'email' => 'test_1@example.com']),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertExpression(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{order_with_null_fk}}')->execute();
        $expression = match ($db->getName()) {
            'mysql' => 'YEAR(NOW())',
            'oci' => "TO_CHAR(SYSDATE, 'YYYY')",
            'pgsql' => "EXTRACT(YEAR FROM TIMESTAMP 'now')",
            'sqlite' => "strftime('%Y')",
            'sqlsrv' => 'YEAR(GETDATE())',
        };
        $command->insert(
            '{{order_with_null_fk}}',
            ['created_at' => new Expression($expression), 'total' => 1],
        )->execute();

        $this->assertEquals(
            1,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM {{order_with_null_fk}}
                SQL
            )->queryScalar(),
        );

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
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $time = time();
        $command->setSql(
            <<<SQL
            DELETE FROM [[order_with_null_fk]]
            SQL
        )->execute();
        $command->insert('{{order}}', ['customer_id' => 1, 'created_at' => $time, 'total' => 42])->execute();

        if ($db->getName() === 'pgsql') {
            $orderId = $db->getLastInsertID('public.order_id_seq');
        } else {
            $orderId = $db->getLastInsertID();
        }

        $columnValueQuery = (new Query($db))->select('{{created_at}}')->from('{{order}}')->where(['id' => $orderId]);
        $command->insert(
            '{{order_with_null_fk}}',
            ['customer_id' => $orderId, 'created_at' => $columnValueQuery, 'total' => 42],
        )->execute();

        $this->assertEquals(
            $time,
            $command->setSql(
                <<<SQL
                SELECT [[created_at]] FROM [[order_with_null_fk]] WHERE [[customer_id]] = :id
                SQL
            )->bindValues([':id' => $orderId])->queryScalar(),
        );

        $command->setSql(
            <<<SQL
            DELETE FROM [[order_with_null_fk]]
            SQL
        )->execute();
        $command->setSql(
            <<<SQL
            DELETE FROM [[order]]
            SQL
        )->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertSelect(): void
    {
        $db = $this->getConnection(true);

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
        $query = (new Query($db))
            ->select(['{{customer}}.{{email}} as name', '{{name}} as email', '{{address}}'])
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testInsertSelectAlias(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{customer}}')->execute();
        $command->insert(
            '{{customer}}',
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ]
        )->execute();
        $query = (new Query($db))
            ->select(['email' => '{{customer}}.{{email}}', 'address' => 'name', 'name' => 'address'])
            ->from('{{customer}}')
            ->where(['and', ['<>', 'name', 'foo'], ['status' => [0, 1, 2, 3]]]);
        $command->insert('{{customer}}', $query)->execute();

        $this->assertEquals(
            2,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM [[customer]]
                SQL
            )->queryScalar(),
        );

        $record = $command->setSql(
            <<<SQL
            SELECT [[email]], [[name]], [[address]] FROM [[customer]]
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
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{type}}')->execute();
        $columns = [
            'int_col' => 1,
            'char_col' => 'test',
            'float_col' => 3.14,
            'bool_col' => true,
            'blob_col' => serialize(['test' => 'data', 'num' => 222]),
        ];
        $command->insert('{{type}}', $columns)->execute();
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
        $db = $this->getConnection(true);

        $this->expectException(IntegrityException::class);

        $command = $db->createCommand(
            <<<SQL
            INSERT INTO [[profile]] ([[id]], [[description]]) VALUES (123, 'duplicate')
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
    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnection(true);

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQuery(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM [[customer]]
            SQL
        );

        $this->assertNull($command->getPdoStatement());

        $reader = $command->query();

        $this->assertNotNull($command->getPdoStatement());
        $this->assertInstanceOf(DataReaderInterface::class, $reader);
        $this->assertIsInt($reader->count());

        $expectedRow = 6;

        if ($db->getName() === 'oci' || $db->getName() === 'pgsql') {
            $expectedRow = 7;
        }

        foreach ($reader as $row) {
            $this->assertIsArray($row);
            $this->assertCount($expectedRow, $row);
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
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        );
        $rows = $command->queryAll();
        $expectedRow = 6;

        if ($db->getName() === 'oci' || $db->getName() === 'pgsql') {
            $expectedRow = 7;
        }

        $this->assertIsArray($rows);
        $this->assertCount(3, $rows);
        $this->assertIsArray($rows[0]);
        $this->assertCount($expectedRow, $rows[0]);

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryCache(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))->select(['{{name}}'])->from('{{customer}}');
        $command = $db->createCommand();
        $update = $command->setSql(
            <<<SQL
            UPDATE [[customer]] SET [[name]] = :name WHERE [[id]] = :id
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
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM [[customer]]
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
            SELECT * FROM [[customer]] where id = 100
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
    public function testQueryOne(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT * FROM [[customer]] ORDER BY [[id]]
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
        SELECT * FROM [[customer]] WHERE [[id]] = 10
        SQL;
        $command = $command->setSql($sql);

        $this->assertNull($command->queryOne());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testQueryScalar(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT * FROM [[customer]] ORDER BY [[id]]
        SQL;

        $this->assertEquals(1, $command->setSql($sql)->queryScalar());

        $sql = <<<SQL
        SELECT [[id]] FROM [[customer]] ORDER BY [[id]]
        SQL;
        $command->setSql($sql)->prepare();

        $this->assertEquals(1, $command->queryScalar());

        $command = $command->setSql(
            <<<SQL
            SELECT [[id]] FROM [[customer]] WHERE [[id]] = 10
            SQL
        );

        $this->assertFalse($command->queryScalar());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testRenameColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $command->renameColumn('{{customer}}', 'address', 'address_city')->execute();

        $this->assertContains('address_city', $schema->getTableSchema('{{customer}}')->getColumnNames());
        $this->assertNotContains('address', $schema->getTableSchema('{{customer}}')->getColumnNames());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testRenameTable(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{new_type}}') !== null) {
            $command->dropTable('{{new_type}}')->execute();
        }

        $this->assertNotNull($schema->getTableSchema('{{type}}'));
        $this->assertNull($schema->getTableSchema('{{new_type}}'));

        $command->renameTable('{{type}}', '{{new_type}}')->execute();

        $this->assertNull($schema->getTableSchema('{{type}}', true));
        $this->assertNotNull($schema->getTableSchema('{{new_type}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testSetRetryHandler(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->assertNull($db->getTransaction());

        $command->setSql(
            <<<SQL
            INSERT INTO [[profile]] ([[description]]) VALUES('command retry')
            SQL
        )->execute();

        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            1,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM [[profile]] WHERE [[description]] = 'command retry'
                SQL
            )->queryScalar()
        );

        $attempts = null;
        $hitHandler = false;
        $hitCatch = false;
        $command->setSql(
            <<<SQL
            INSERT INTO [[profile]] ([[id]], [[description]]) VALUES(1, 'command retry')
            SQL
        );

        $command->setRetryHandler(
            static function ($exception, $attempt) use (&$attempts, &$hitHandler) {
                $attempts = $attempt;
                $hitHandler = true;

                return $attempt <= 2;
            }
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
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testTransaction(): void
    {
        $db = $this->getConnection(true);

        $this->assertNull($db->getTransaction());

        $command = $db->createCommand();
        $command = $command->setSql(
            <<<SQL
            INSERT INTO [[profile]] ([[description]]) VALUES('command transaction')
            SQL
        );

        Assert::invokeMethod($command, 'requireTransaction');

        $command->execute();

        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            1,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM [[profile]] WHERE [[description]] = 'command transaction'
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
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $rows = $command->setSql(
            <<<SQL
            SELECT * FROM [[animal]]
            SQL
        )->queryAll();

        $this->assertCount(2, $rows);

        $command->truncateTable('{{animal}}')->execute();
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
     * @throws Throwable
     */
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

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::upsert()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->assertEquals(
            0,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM [[T_upsert]]
                SQL,
            )->queryScalar()
        );

        $this->performAndCompareUpsertResult($db, $firstData);

        $this->assertEquals(
            1,
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM [[T_upsert]]
                SQL,
            )->queryScalar()
        );

        $this->performAndCompareUpsertResult($db, $secondData);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function performAndCompareUpsertResult(ConnectionPDOInterface $db, array $data): void
    {
        $params = $data['params'];
        $expected = $data['expected'] ?? $params[1];

        $command = $db->createCommand();

        call_user_func_array([$command, 'upsert'], $params);

        $command->execute();

        $actual = (new Query($db))
            ->select(['email', 'address' => new Expression($this->upsertTestCharCast), 'status'])
            ->from('{{T_upsert}}')
            ->one();
        $this->assertEquals($expected, $actual, $this->upsertTestCharCast);
    }
}
