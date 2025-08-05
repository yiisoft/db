<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Throwable;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbUuidHelper;
use Yiisoft\Db\Query\DataReaderInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\AbstractCommandTest;
use Yiisoft\Db\Tests\Provider\CommandProvider;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;

use function array_filter;
use function is_string;
use function setlocale;
use function str_starts_with;

abstract class CommonCommandTest extends AbstractCommandTest
{
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

        $command->addCheck('{{test_ck}}', '{{test_ck_constraint}}', '{{int1}} > 1')->execute();

        $this->assertMatchesRegularExpression(
            '/^.*int1.*>.*1.*$/',
            $schema->getTableChecks('{{test_ck}}')['test_ck_constraint']->expression
        );

        $db->close();
    }

    public function testAddColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->addColumn('{{customer}}', '{{city}}', ColumnType::STRING)->execute();

        $this->assertTrue($db->getTableSchema('{{customer}}')->getColumn('city') !== null);
        $this->assertSame(
            ColumnType::STRING,
            $db->getTableSchema('{{customer}}')->getColumn('city')->getType(),
        );

        $db->close();
    }

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

        $db->close();
    }

    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection(true);

        $tableName = '{{customer}}';
        $commentText = 'Customer table.';

        $command = $db->createCommand();
        $command->addCommentOnTable($tableName, $commentText)->execute();
        $commentOnTable = $db->getSchema()->getTableSchema($tableName, true)->getComment();

        $this->assertSame($commentText, $commentOnTable);

        $db->close();
    }

    public function testResetSequenceSql(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->assertEmpty($command->getRawSql());
        $command->resetSequence('{{%customer}}');
        $this->assertNotEmpty($command->getRawSql());

        $db->close();
    }

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_def}}') !== null) {
            $command->dropTable('{{test_def}}')->execute();
        }

        $command->createTable('{{test_def}}', ['int1' => ColumnType::INTEGER])->execute();

        $this->assertEmpty($schema->getTableDefaultValues('{{test_def}}'));

        $command->addDefaultValue('{{test_def}}', '{{test_def_constraint}}', 'int1', 41)->execute();

        $this->assertMatchesRegularExpression(
            '/^.*41.*$/',
            $schema->getTableDefaultValues('{{test_def}}')['test_def_constraint']->value,
        );

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'addForeignKey')]
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

        $this->assertEmpty($schema->getTableForeignKeys($tableName));

        $command->addForeignKey($tableName, $name, $column1, $tableName, $column2)->execute();
        $foreignKey = $schema->getTableForeignKeys($tableName)[$db->getQuoter()->getRawTableName($name)];

        $this->assertSame($expectedName, $foreignKey->name);

        if (is_string($column1)) {
            $column1 = [$column1];
        }

        $this->assertSame($column1, $foreignKey->columnNames);

        if (is_string($column2)) {
            $column2 = [$column2];
        }

        $this->assertSame($column2, $foreignKey->foreignColumnNames);

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'addPrimaryKey')]
    public function testAddPrimaryKey(string $name, string $tableName, array|string $column): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName));

        $db->createCommand()->addPrimaryKey($tableName, $name, $column)->execute();

        if (is_string($column)) {
            $column = [$column];
        }

        $this->assertSame($column, $schema->getTablePrimaryKey($tableName)->columnNames);

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'addUnique')]
    public function testAddUnique(string $name, string $tableName, array|string $column): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableUniques($tableName));

        $command->addUnique($tableName, $name, $column)->execute();

        if (is_string($column)) {
            $column = [$column];
        }

        $unique = $schema->getTableUniques($tableName)[$db->getQuoter()->getRawTableName($name)];

        $this->assertSame($column, $unique->columnNames);

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $values,
        array $columns,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insertBatch($table, $values, $columns);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());

        $command->prepare(false);
        $command->execute();

        $this->assertEquals($insertedRow, (new Query($db))->from($table)->count());

        $db->close();
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
            if ($db->getDriverName() === 'oci') {
                $command->setSql(
                    <<<SQL
                    ALTER SESSION SET NLS_NUMERIC_CHARACTERS='.,'
                    SQL
                )->execute();
            }

            /* batch insert on "type" table */
            $command->insertBatch('{{type}}', $data, $cols)->execute();
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
        } catch (Exception|Throwable $e) {
            setlocale(LC_NUMERIC, $locale);

            throw $e;
        }

        setlocale(LC_NUMERIC, $locale);

        $db->close();
    }

    public function testBatchInsertWithDuplicates(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insertBatch(
            '{{customer}}',
            [['t1@example.com', 'test_name', 'test_address']],
            ['email', 'name', 'address'],
        );

        $this->assertSame(1, $command->execute());

        $result = (new Query($db))
            ->select(['email', 'name', 'address'])
            ->from('{{customer}}')
            ->where(['=', '{{email}}', 't1@example.com'])
            ->one();

        $this->assertCount(3, $result);
        $this->assertSame(['email' => 't1@example.com', 'name' => 'test_name', 'address' => 'test_address'], $result);

        $db->close();
    }

    public function testBatchInsertWithManyData(): void
    {
        $db = $this->getConnection(true);

        $values = [];
        $attemptsInsertRows = 200;
        $command = $db->createCommand();

        for ($i = 0; $i < $attemptsInsertRows; $i++) {
            $values[$i] = ['t' . $i . '@any.com', 't' . $i, 't' . $i . ' address'];
        }

        $command->insertBatch('{{customer}}', $values, ['email', 'name', 'address']);

        $this->assertSame($attemptsInsertRows, $command->execute());

        $insertedRowsCount = (new Query($db))->from('{{customer}}')->count();

        $this->assertGreaterThanOrEqual($attemptsInsertRows, $insertedRowsCount);

        $db->close();
    }

    public function testBatchInsertWithYield(): void
    {
        $db = $this->getConnection(true);

        $rows = (
            static function () {
                yield ['test@email.com', 'test name', 'test address'];
            }
        )();
        $command = $db->createCommand();
        $command->insertBatch('{{customer}}', $rows, ['email', 'name', 'address']);

        $this->assertSame(1, $command->execute());

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'createIndex')]
    public function testCreateIndex(array $columns, array $indexColumns, string|null $indexType, string|null $indexMethod): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $tableName = 'test_create_index';
        $indexName = 'test_index_name';

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, $columns)->execute();

        $count = count($schema->getTableIndexes($tableName));
        $command->createIndex($tableName, $indexName, $indexColumns, $indexType, $indexMethod)->execute();

        $this->assertCount($count + 1, $schema->getTableIndexes($tableName));

        $index = array_filter($schema->getTableIndexes($tableName), static fn ($index) => !$index->isPrimaryKey)[$indexName];

        $this->assertSame($indexColumns, $index->columnNames);

        if ($indexType !== null && str_starts_with($indexType, 'UNIQUE')) {
            $this->assertTrue($index->isUnique);
        } else {
            $this->assertFalse($index->isUnique);
        }

        $db->close();
    }

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
            [
                '[[id]]' => PseudoType::PK,
                '[[bar]]' => ColumnType::INTEGER,
                '[[name]]' => ColumnBuilder::string(100)->notNull(),
            ],
        )->execute();
        $command->insert('{{testCreateTable}}', ['[[bar]]' => 1, '[[name]]' => 'Lilo'])->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[id]], [[bar]], [[name]] FROM [[testCreateTable]];
            SQL
        )->queryAll();

        $nameCol = $schema->getTableSchema('{{testCreateTable}}', true)->getColumn('name');

        $this->assertTrue($nameCol->isNotNull());
        $this->assertEquals([['id' => 1, 'bar' => 1, 'name' => 'Lilo']], $records);

        $db->close();
    }

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
            ['id' => PseudoType::PK, 'bar' => ColumnType::INTEGER],
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

        $db->close();
    }

    public function testDataReaderRewindException(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $reader = $command->setSql('SELECT * FROM {{customer}}')->query();

        $this->assertTrue($reader->valid());

        $firstRow = $reader->current();

        $this->assertIsArray($firstRow);

        $reader->rewind();

        $this->assertTrue($reader->valid());
        $this->assertSame($firstRow, $reader->current());

        $reader->next();

        $this->expectException(InvalidCallException::class);
        $this->expectExceptionMessage('DataReader cannot rewind. It is a forward-only reader.');

        $reader->rewind();

        $db->close();
    }

    public function testDataReaderIndexByAndResultCallback(): void
    {
        $db = $this->getConnection(true);

        $reader = $db->createCommand()
            ->setSql('SELECT * FROM {{customer}} WHERE [[id]]=1')
            ->query()
            ->indexBy(static fn (array $row): int => (int) $row['id'])
            ->resultCallback(static fn (array $row): object => (object) $row);

        $this->assertTrue($reader->valid());
        $this->assertSame(1, $reader->key());
        $this->assertIsObject($reader->current());

        $reader->next();

        $this->assertFalse($reader->valid());
        $this->assertNull($reader->key());
        $this->assertFalse($reader->current());

        $db->close();
    }

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

        $db->close();
    }

    public function testDropCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_ck}}') !== null) {
            $command->dropTable('{{test_ck}}')->execute();
        }

        $command->createTable('{{test_ck}}', ['int1' => 'integer'])->execute();

        $this->assertEmpty($schema->getTableChecks('{{test_ck}}'));

        $command->addCheck('{{test_ck}}', '{{test_ck_constraint}}', '[[int1]] > 1')->execute();

        $this->assertMatchesRegularExpression(
            '/^.*int1.*>.*1.*$/',
            $schema->getTableChecks('{{test_ck}}')['test_ck_constraint']->expression,
        );

        $command->dropCheck('{{test_ck}}', '{{test_ck_constraint}}')->execute();

        $this->assertEmpty($schema->getTableChecks('{{test_ck}}'));

        $db->close();
    }

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
            [
                'id' => PseudoType::PK,
                'bar' => ColumnType::INTEGER,
                'baz' => ColumnType::INTEGER,
            ],
        )->execute();
        $command->dropColumn('{{testDropColumn}}', 'bar')->execute();

        $this->assertArrayNotHasKey('bar', $schema->getTableSchema('{{testDropColumn}}')->getColumns());
        $this->assertArrayHasKey('baz', $schema->getTableSchema('{{testDropColumn}}')->getColumns());

        $db->close();
    }

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

        $db->close();
    }

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

        $db->close();
    }

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

        $command->addDefaultValue('{{test_def}}', '{{test_def_constraint}}', 'int1', 41)->execute();

        $this->assertMatchesRegularExpression(
            '/^.*41.*$/',
            $schema->getTableDefaultValues('{{test_def}}')['test_def_constraint']->value,
        );

        $command->dropDefaultValue('{{test_def}}', '{{test_def_constraint}}')->execute();

        $this->assertEmpty($schema->getTableDefaultValues('{{test_def}}'));

        $db->close();
    }

    public function testDropForeignKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_fk}}') !== null) {
            $command->dropTable('{{test_fk}}')->execute();
        }

        $command->createTable('{{test_fk}}', ['id' => PseudoType::PK, 'int1' => 'integer'])->execute();

        $this->assertEmpty($schema->getTableForeignKeys('{{test_fk}}', true));

        $command->addForeignKey('{{test_fk}}', '{{test_fk_constraint}}', 'int1', '{{test_fk}}', 'id')->execute();

        $this->assertNotEmpty($schema->getTableForeignKeys('{{test_fk}}', true));

        $command->dropForeignKey('{{test_fk}}', '{{test_fk_constraint}}')->execute();

        $this->assertEmpty($schema->getTableForeignKeys('{{test_fk}}', true));

        $db->close();
    }

    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_idx}}') !== null) {
            $command->dropTable('{{test_idx}}')->execute();
        }

        $command->createTable('{{test_idx}}', ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableIndexes('{[test_idx}}'));

        $command->createIndex('{{test_idx}}', '{{test_idx_constraint}}', ['int1', 'int2'], 'UNIQUE')->execute();
        $index = $schema->getTableIndexes('{{test_idx}}')['test_idx_constraint'];

        $this->assertSame(['int1', 'int2'], $index->columnNames);
        $this->assertTrue($index->isUnique);

        $command->dropIndex('{{test_idx}}', '{{test_idx_constraint}}')->execute();

        $this->assertEmpty($schema->getTableIndexes('{{test_idx}}'));

        $db->close();
    }

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

        $command->addPrimaryKey('{{test_pk}}', '{{test_pk_constraint}}', ['int1', 'int2'])->execute();

        $this->assertSame(['int1', 'int2'], $schema->getTableSchema('{{test_pk}}', true)->getColumnNames());

        $command->dropPrimaryKey('{{test_pk}}', '{{test_pk_constraint}}')->execute();

        $this->assertEmpty($schema->getTableSchema('{{test_pk}}', true)->getPrimaryKey());

        $db->close();
    }

    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{testDropTable}}') !== null) {
            $command->dropTable('{{testDropTable}}')->execute();
        }

        $command->createTable('{{testDropTable}}', ['id' => PseudoType::PK, 'foo' => 'integer'])->execute();

        $this->assertNotNull($schema->getTableSchema('{{testDropTable}}', true));

        $command->dropTable('{{testDropTable}}')->execute();

        $this->assertNull($schema->getTableSchema('{{testDropTable}}', true));

        $db->close();
    }

    public function testDropTableIfExistsWithExistTable(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{testDropTable}}') !== null) {
            $command->dropTable('{{testDropTable}}')->execute();
        }

        $command->createTable('{{testDropTable}}', ['id' => PseudoType::PK, 'foo' => 'integer'])->execute();
        $this->assertNotNull($schema->getTableSchema('{{testDropTable}}', true));

        $command->dropTable('{{testDropTable}}', ifExists: true)->execute();
        $this->assertNull($schema->getTableSchema('{{testDropTable}}', true));

        $db->close();
    }

    public function testDropTableIfExistsWithNonExistTable(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{testDropTable}}') !== null) {
            $command->dropTable('{{testDropTable}}')->execute();
        }

        $command->dropTable('{{testDropTable}}', ifExists: true)->execute();
        $this->assertNull($schema->getTableSchema('{{testDropTable}}', true));

        $db->close();
    }

    public function testDropTableCascade(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{testCascadeDropTable2}}') !== null) {
            $command->dropTable('{{testCascadeDropTable2}}')->execute();
        }
        if ($schema->getTableSchema('{{testCascadeDropTable}}') !== null) {
            $command->dropTable('{{testCascadeDropTable}}')->execute();
        }

        $command->createTable(
            '{{testCascadeDropTable}}',
            ['id' => 'integer not null unique', 'foo' => 'integer'],
        )->execute();
        $this->assertNotNull($schema->getTableSchema('{{testCascadeDropTable}}', true));

        $command->createTable(
            '{{testCascadeDropTable2}}',
            ['id' => 'integer not null unique', 'foreign_id' => 'integer'],
        )->execute();
        $this->assertNotNull($schema->getTableSchema('{{testCascadeDropTable2}}', true));

        $command
            ->addForeignKey('{{testCascadeDropTable2}}', 'fgk', 'foreign_id', '{{testCascadeDropTable}}', 'id')
            ->execute();
        $this->assertNotEmpty($schema->getTableForeignKeys('{{testCascadeDropTable2}}', true));

        $command->dropTable('{{testCascadeDropTable}}', cascade: true)->execute();
        $this->assertNull($schema->getTableSchema('{{testCascadeDropTable}}', true));
        $this->assertEmpty($schema->getTableForeignKeys('{{testCascadeDropTable2}}', true));

        $db->close();
    }

    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_uq}}') !== null) {
            $command->dropTable('{{test_uq}}')->execute();
        }

        $command->createTable('{{test_uq}}', ['int1' => 'integer not null', 'int2' => 'integer not null'])->execute();

        $this->assertEmpty($schema->getTableUniques('{{test_uq}}'));

        $command->addUnique('{{test_uq}}', '{{test_uq_constraint}}', ['int1'])->execute();

        $this->assertSame(['int1'], $schema->getTableUniques('{{test_uq}}')['test_uq_constraint']->columnNames);

        $command->dropUnique('{{test_uq}}', '{{test_uq_constraint}}')->execute();

        $this->assertEmpty($schema->getTableUniques('{{test_uq}}'));

        $db->close();
    }

    public function testDropView(): void
    {
        $db = $this->getConnection(true);

        /* since it already exists in the fixtures */
        $viewName = '{{animal_view}}';

        $schema = $db->getSchema();

        $this->assertNotNull($schema->getTableSchema($viewName));

        $db->createCommand()->dropView($viewName)->execute();

        $this->assertNull($schema->getTableSchema($viewName));

        $db->close();
    }

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
        $message = match ($db->getDriverName()) {
            'pgsql' => 'SQLSTATE[42601]',
            'sqlite', 'oci' => 'SQLSTATE[HY000]',
            default => 'SQLSTATE[42000]',
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        $command->execute();
    }

    public function testExecuteWithoutSql(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $result = $command->setSql('')->execute();

        $this->assertSame(0, $result);

        $db->close();
    }

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

        $db->close();
    }

    public function testInsertReturningPks(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $expected = match ($db->getDriverName()) {
            'pgsql' => ['id' => 4],
            default => ['id' => '4'],
        };

        $this->assertSame(
            $expected,
            $command->insertReturningPks('{{customer}}', ['name' => 'test_1', 'email' => 'test_1@example.com']),
        );

        $db->close();
    }

    public function testInsertReturningPksWithCompositePK(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $params = ['id_1' => 99, 'id_2' => 100.5, 'type' => 'test'];
        $result = $command->insertReturningPks('{{%notauto_pk}}', $params);

        $this->assertEquals($params['id_1'], $result['id_1']);
        $this->assertEquals($params['id_2'], $result['id_2']);

        $db->close();
    }

    public function testInsertExpression(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{order_with_null_fk}}')->execute();
        $expression = match ($db->getDriverName()) {
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

        $db->close();
    }

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

        if ($db->getDriverName() === 'pgsql') {
            $orderId = $db->getLastInsertId('public.order_id_seq');
        } else {
            $orderId = $db->getLastInsertId();
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

        $db->close();
    }

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

        $db->close();
    }

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

        $db->close();
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with wrong query object.
     */
    #[DataProviderExternal(CommandProvider::class, 'invalidSelectColumns')]
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

        $db->close();
    }

    public function testInsertWithoutTypecasting(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $values = [
            'int_col' => '1',
            'char_col' => 'test',
            'float_col' => '3.14',
            'bool_col' => '1',
        ];

        $command->insert('{{type}}', $values);

        $this->assertSame([
            ':qp0' => 1,
            ':qp1' => 'test',
            ':qp2' => 3.14,
            ':qp3' => $db->getDriverName() === 'oci' ? '1' : true,
        ], $command->getParams());

        $command = $command->withDbTypecasting(false);
        $command->insert('{{type}}', $values);

        $this->assertSame([
            ':qp0' => '1',
            ':qp1' => 'test',
            ':qp2' => '3.14',
            ':qp3' => '1',
        ], $command->getParams());

        $db->close();
    }

    public function testInsertBatchWithoutTypecasting(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $values = [
            'int_col' => '1',
            'char_col' => 'test',
            'float_col' => '3.14',
            'bool_col' => '1',
        ];

        $command->insertBatch('{{type}}', [$values]);

        $expectedParams = [':qp0' => 'test'];

        if ($db->getDriverName() === 'oci') {
            $expectedParams[':qp1'] = '1';
        }

        $this->assertSame($expectedParams, $command->getParams());

        $command = $command->withDbTypecasting(false);
        $command->insertBatch('{{type}}', [$values]);

        $this->assertSame([
            ':qp0' => '1',
            ':qp1' => 'test',
            ':qp2' => '3.14',
            ':qp3' => '1',
        ], $command->getParams());

        $db->close();
    }

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

    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert(
            '{{customer}}',
            ['name' => 'Some {{weird}} name', 'email' => 'test@example.com', 'address' => 'Some {{%weird}} address']
        )->execute();

        if ($db->getDriverName() === 'pgsql') {
            $customerId = $db->getLastInsertId('public.customer_id_seq');
        } else {
            $customerId = $db->getLastInsertId();
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

        $db->close();
    }

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
        $db = $this->getConnection(true);

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

        $db->close();
    }

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

        $db->close();
    }

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

        $db->close();
    }

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

        $db->close();
    }

    public function testRenameColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $command->renameColumn('{{customer}}', 'address', 'address_city')->execute();

        $this->assertContains('address_city', $schema->getTableSchema('{{customer}}')->getColumnNames());
        $this->assertNotContains('address', $schema->getTableSchema('{{customer}}')->getColumnNames());

        $db->close();
    }

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

        $db->close();
    }

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

        $db->close();
    }

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

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        array $expectedValues,
        int $expectedCount,
    ): void {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $count = $command->update($table, $columns, $conditions, $params)->execute();

        $this->assertSame($expectedCount, $count);

        $values = (new Query($db))
            ->from($table)
            ->where($conditions, $params)
            ->limit(1)
            ->one();

        foreach ($expectedValues as $name => $expectedValue) {
            $this->assertEquals($expectedValue, $values[$name]);
        }

        $db->close();
    }

    public function testUpdateWithoutTypecasting(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $values = [
            'int_col' => '1',
            'char_col' => 'test',
            'float_col' => '3.14',
            'bool_col' => '1',
        ];

        $command->update('{{type}}', $values);

        $this->assertSame([
            ':qp0' => 1,
            ':qp1' => 'test',
            ':qp2' => 3.14,
            ':qp3' => $db->getDriverName() === 'oci' ? '1' : true,
        ], $command->getParams());

        $command = $command->withDbTypecasting(false);
        $command->update('{{type}}', $values);

        $this->assertSame([
            ':qp0' => '1',
            ':qp1' => 'test',
            ':qp2' => '3.14',
            ':qp3' => '1',
        ], $command->getParams());
    }

    #[DataProviderExternal(CommandProvider::class, 'upsert')]
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

        $db->close();
    }

    public function testPrepareWithEmptySql()
    {
        $db = $this->createMock(PdoConnectionInterface::class);
        $db->expects(self::never())->method('getActivePdo');

        $command = new class ($db) extends AbstractPdoCommand {
            public function showDatabases(): array
            {
                return $this->showDatabases();
            }

            protected function getQueryBuilder(): QueryBuilderInterface
            {
            }

            protected function internalExecute(): void
            {
            }
        };

        $command->prepare();
    }

    protected function performAndCompareUpsertResult(PdoConnectionInterface $db, array $data): void
    {
        $params = [];

        foreach ($data['params'] as $param) {
            if (is_callable($param)) {
                $params[] = $param($db);
            } else {
                $params[] = $param;
            }
        }

        $expected = $data['expected'] ?? $params[1];

        $command = $db->createCommand();

        ($command->upsert(...))(...$params);

        $command->execute();

        $actual = (new Query($db))
            ->select(['email', 'address' => new Expression($this->upsertTestCharCast), 'status'])
            ->from('{{T_upsert}}')
            ->one();
        $this->assertEquals($expected, $actual, $this->upsertTestCharCast);
    }

    public function testDecimalValue(): void
    {
        $decimalValue = 10.0;
        $db = $this->getConnection(true);

        $inserted = $db->createCommand()
            ->insertReturningPks(
                '{{%order}}',
                ['customer_id' => 1, 'created_at' => 0, 'total' => $decimalValue]
            );

        $result = $db->createCommand(
            'select * from {{%order}} where [[id]]=:id',
            ['id' => $inserted['id']]
        )->queryOne();

        $column = $db->getTableSchema('{{%order}}')->getColumn('total');
        $phpTypecastValue = $column->phpTypecast($result['total']);

        $this->assertSame($decimalValue, $phpTypecastValue);
    }

    public function testInsertReturningPksEmptyValues()
    {
        $db = $this->getConnection(true);

        $pkValues = $db->createCommand()->insertReturningPks('null_values', []);

        $expected = match ($db->getDriverName()) {
            'pgsql' => ['id' => 1],
            default => ['id' => '1'],
        };

        $this->assertSame($expected, $pkValues);
    }

    public function testInsertReturningPksWithQuery(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))->select([
            'name' => new Expression("'test_1'"),
            'email' => new Expression("'test_1@example.com'"),
        ]);

        $pkValues = $db->createCommand()->insertReturningPks('customer', $query);

        $this->assertEquals(['id' => 4], $pkValues);
    }

    public function testInsertReturningPksEmptyValuesAndNoPk()
    {
        $db = $this->getConnection(true);

        $pkValues = $db->createCommand()->insertReturningPks('negative_default_values', []);

        $this->assertSame([], $pkValues);
    }

    public function testInsertReturningPksWithPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

        $result = $db->createCommand()
            ->withPhpTypecasting()
            ->insertReturningPks('notauto_pk', ['id_1' => 1, 'id_2' => 2.5, 'type' => 'test1']);

        $this->assertSame(['id_1' => 1, 'id_2' => 2.5], $result);
    }

    #[DataProviderExternal(CommandProvider::class, 'upsertReturning')]
    public function testUpsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array|null $returnColumns,
        array $selectCondition,
        array $expectedValues,
    ): void {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $returnedValues = $command->upsertReturning($table, $insertColumns, $updateColumns, $returnColumns);

        $this->assertEquals($expectedValues, $returnedValues);

        if (!empty($returnColumns)) {
            $selectedValues = (new Query($db))
                ->select(array_keys($expectedValues))
                ->from($table)
                ->where($selectCondition)
                ->one();

            $this->assertEquals($expectedValues, $selectedValues);
        }

        $db->close();
    }

    public function testUpsertReturningWithUnique(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $tableName = 'T_upsert';
        $insertColumns = [
            'email' => 'test@example.com',
            'address' => 'first address',
            'status' => 1,
        ];
        $expectedValues = [
            'id' => 1,
            'ts' => null,
            'email' => 'test@example.com',
            'recovery_email' => null,
            'address' => 'first address',
            'status' => 1,
            'orders' => 0,
            'profile_id' => null,
        ];

        $returnedValues = $command->upsertReturning($tableName, $insertColumns);

        $this->assertEquals($expectedValues, $returnedValues);

        $insertColumns = [
            'email' => 'test@example.com',
            'address' => 'second address',
            'status' => 2,
        ];

        $returnedValues = $command->upsertReturning($tableName, $insertColumns, false);

        $this->assertEquals($expectedValues, $returnedValues);

        $returnedValues = $command->upsertReturning($tableName, $insertColumns, ['address' => 'third address']);
        $expectedValues['address'] = 'third address';

        $this->assertEquals($expectedValues, $returnedValues);

        $db->close();
    }

    public function testUpsertReturningPks(): void
    {
        $db = $this->getConnection(true);

        // insert case
        $primaryKeys = $db->createCommand()
            ->upsertReturningPks('{{customer}}', ['name' => 'test_1', 'email' => 'test_1@example.com']);

        $this->assertEquals(['id' => 4], $primaryKeys);

        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE [[id]] = 4')->queryOne();

        $this->assertSame('test_1', $customer['name']);
        $this->assertSame('test_1@example.com', $customer['email']);

        // update case with composite primary key
        $primaryKeys = $db->createCommand()->upsertReturningPks(
            '{{order_item}}',
            ['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 100],
        );

        $this->assertEquals(['order_id' => 1, 'item_id' => 2], $primaryKeys);

        $orderItem = $db->createCommand('SELECT * FROM {{order_item}} WHERE [[order_id]] = 1 AND [[item_id]] = 2')->queryOne();

        $this->assertEquals(3, $orderItem['quantity']);
        $this->assertEquals(100, $orderItem['subtotal']);

        $db->close();
    }

    public function testUpsertReturningPksEmptyValues()
    {
        $db = $this->getConnection(true);

        $pkValues = $db->createCommand()->upsertReturningPks('null_values', []);

        $this->assertEquals(['id' => 1], $pkValues);
    }

    public function testUpsertReturningPksEmptyValuesAndNoPk()
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $pkValues = $command->upsertReturningPks('negative_default_values', []);

        $this->assertSame([], $pkValues);
    }

    public function testUpsertReturningPksWithPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

        $result = $db->createCommand()
            ->withPhpTypecasting()
            ->upsertReturningPks('notauto_pk', ['id_1' => 1, 'id_2' => 2.5, 'type' => 'test1']);

        $this->assertSame(['id_1' => 1, 'id_2' => 2.5], $result);

        $result = $db->createCommand()
            ->withPhpTypecasting()
            ->upsertReturningPks('notauto_pk', ['id_1' => 2, 'id_2' => 2.5, 'type' => 'test2']);

        $this->assertSame(['id_1' => 2, 'id_2' => 2.5], $result);

        $result = $db->createCommand()
            ->withPhpTypecasting()
            ->upsertReturningPks('notauto_pk', ['id_1' => 2, 'id_2' => 2.5, 'type' => 'test3']);

        $this->assertSame(['id_1' => 2, 'id_2' => 2.5], $result);
    }

    public function testUuid(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $tableName = '{{%test_uuid}}';
        if ($db->getTableSchema($tableName, true)) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, [
            'uuid_pk' => ColumnBuilder::uuidPrimaryKey(),
            'int_col' => ColumnBuilder::integer(),
        ])->execute();
        $tableSchema = $db->getTableSchema($tableName, true);
        $this->assertNotNull($tableSchema);

        $uuidValue = $uuidSource = '738146be-87b1-49f2-9913-36142fb6fcbe';

        $uuidValue = match ($db->getDriverName()) {
            'oci' => new Expression("HEXTORAW(REPLACE(:uuid, '-', ''))", [':uuid' => $uuidValue]),
            'mysql' => new Expression("UNHEX(REPLACE(:uuid, '-', ''))", [':uuid' => $uuidValue]),
            'sqlite' => new Param(DbUuidHelper::uuidToBlob($uuidValue), DataType::LOB),
            'sqlsrv' => new Expression('CONVERT(uniqueidentifier, :uuid)', [':uuid' => $uuidValue]),
            default => $uuidValue,
        };

        $command->insert($tableName, [
            'int_col' => 1,
            'uuid_pk' => $uuidValue,
        ])->execute();

        $uuid = (new Query($db))
            ->select(['[[uuid_pk]]'])
            ->from($tableName)
            ->where(['int_col' => 1])
            ->scalar();

        $uuidString = strtolower(DbUuidHelper::toUuid($uuid));

        $this->assertSame($uuidSource, $uuidString);

        $db->close();
    }

    public function testJsonTable(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        if ($db->getTableSchema('json_table', true) !== null) {
            $command->dropTable('json_table')->execute();
        }

        $command->createTable('json_table', [
            'id' => PseudoType::PK,
            'json_col' => ColumnBuilder::json(),
        ])->execute();

        $command->insert('json_table', ['json_col' => ['a' => 1, 'b' => 2]]);

        $typeHint = $db->getDriverName() === 'pgsql' ? '::jsonb' : '';
        $expectedValue = $db->getQuoter()->quoteValue('{"a":1,"b":2}') . $typeHint;

        $this->assertSame(
            DbHelper::replaceQuotes(
                "INSERT INTO [[json_table]] ([[json_col]]) VALUES (:qp0$typeHint)",
                $db->getDriverName(),
            ),
            $command->getSql()
        );
        $this->assertEquals([':qp0' => new Param('{"a":1,"b":2}', DataType::STRING)], $command->getParams(false));
        $this->assertSame(
            DbHelper::replaceQuotes(
                "INSERT INTO [[json_table]] ([[json_col]]) VALUES ($expectedValue)",
                $db->getDriverName(),
            ),
            $command->getRawSql()
        );
        $this->assertSame(1, $command->execute());

        $tableSchema = $db->getTableSchema('json_table', true);
        $this->assertNotNull($tableSchema);
        $this->assertSame('json_col', $tableSchema->getColumn('json_col')->getName());
        $this->assertSame(ColumnType::JSON, $tableSchema->getColumn('json_col')->getType());

        $value = (new Query($db))->select('json_col')->from('json_table')->where(['id' => 1])->scalar();
        $this->assertSame('{"a":1,"b":2}', str_replace(' ', '', $value));
    }
}
