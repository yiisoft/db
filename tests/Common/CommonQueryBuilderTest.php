<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Value\DateTimeValue;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Tests\Support\Assert;

use function is_array;
use function sort;

use const SORT_NATURAL;

abstract class CommonQueryBuilderTest extends AbstractQueryBuilderTest
{
    private function createTebleWithColumn(CommandInterface $command, string|ColumnInterface $column)
    {
        try {
            $command->dropTable('build_column_definition_primary_key')->execute();
        } catch (Exception) {
        }

        $command->createTable('build_column_definition_primary_key', ['id' => $column])->execute();
    }

    public function getBuildColumnDefinitionProvider(): array
    {
        return QueryBuilderProvider::buildColumnDefinition();
    }

    #[DoesNotPerformAssertions]
    public function testCreateTableWithBuildColumnDefinition(): void
    {
        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();
        $command = $db->createCommand();

        $provider = $this->getBuildColumnDefinitionProvider();

        $i = 0;
        $columns = [];

        foreach ($provider as $data) {
            $column = $data[1];

            if ($column instanceof ColumnInterface) {
                if ($column->isPrimaryKey()) {
                    $this->createTebleWithColumn($command, $column);
                    continue;
                }

                if ($column->getReference() !== null) {
                    continue;
                }
            } elseif ($columnFactory->fromDefinition($column)->isPrimaryKey()) {
                $this->createTebleWithColumn($command, $column);
                continue;
            }

            $name = $column instanceof ColumnInterface ? $column->getName() : null;

            $columns[$name ?? 'col_' . $i++] = $column;
        }

        try {
            $command->dropTable('build_column_definition')->execute();
        } catch (Exception) {
        }

        $command->createTable('build_column_definition', $columns)->execute();
    }

    public function testInsertWithoutTypecasting(): void
    {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $values = [
            'int_col' => '1',
            'char_col' => 'test',
            'float_col' => '3.14',
            'bool_col' => '1',
        ];

        $params = [];
        $qb->insert('{{type}}', $values, $params);

        $this->assertEquals(
            $db->getDriverName() === 'oci'
                ? [':qp0' => new Param('test', DataType::STRING), ':qp1' => new Param('1', DataType::STRING)]
                : [':qp0' => new Param('test', DataType::STRING)],
            $params
        );

        $params = [];
        $qb->withTypecasting(false)->insert('{{type}}', $values, $params);

        $this->assertEquals([
            ':qp0' => new Param('1', DataType::STRING),
            ':qp1' => new Param('test', DataType::STRING),
            ':qp2' => new Param('3.14', DataType::STRING),
            ':qp3' => new Param('1', DataType::STRING),
        ], $params);

        $db->close();
    }

    public function testInsertBatchWithoutTypecasting(): void
    {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $values = [
            'int_col' => '1',
            'char_col' => 'test',
            'float_col' => '3.14',
            'bool_col' => '1',
        ];

        // Test with typecasting enabled
        $expectedParams = [':qp0' => new Param('test', DataType::STRING)];

        if ($db->getDriverName() === 'oci') {
            $expectedParams[':qp1'] = new Param('1', DataType::STRING);
        }

        $params = [];
        $qb->insertBatch('{{type}}', [$values], [], $params);

        Assert::arraysEquals($expectedParams, $params);

        // Test with typecasting disabled
        $expectedParams = [
            ':qp0' => new Param('1', DataType::STRING),
            ':qp1' => new Param('test', DataType::STRING),
            ':qp2' => new Param('3.14', DataType::STRING),
            ':qp3' => new Param('1', DataType::STRING),
        ];

        $params = [];
        $qb->withTypecasting(false)->insertBatch('{{type}}', [$values], [], $params);

        Assert::arraysEquals($expectedParams, $params);

        $db->close();
    }

    public function testUpdateWithoutTypecasting(): void
    {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $values = [
            'int_col' => '1',
            'char_col' => 'test',
            'float_col' => '3.14',
            'bool_col' => '1',
        ];

        $params = [];
        $qb->update('{{type}}', $values, [], null, $params);

        $expectedParams = [':qp0' => new Param('test', DataType::STRING)];

        if ($db->getDriverName() === 'oci') {
            $expectedParams[':qp1'] = new Param('1', DataType::STRING);
        }

        Assert::arraysEquals($expectedParams, $params);

        $params = [];
        $qb->withTypecasting(false)->update('{{type}}', $values, [], null, $params);

        Assert::arraysEquals([
            ':qp0' => new Param('1', DataType::STRING),
            ':qp1' => new Param('test', DataType::STRING),
            ':qp2' => new Param('3.14', DataType::STRING),
            ':qp3' => new Param('1', DataType::STRING),
        ], $params);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'caseXBuilder')]
    public function testCaseXBuilder(
        CaseX $case,
        string $expectedSql,
        array $expectedParams,
        string|int $expectedResult,
    ): void {
        parent::testCaseXBuilder($case, $expectedSql, $expectedParams, $expectedResult);

        $db = $this->getConnection();

        $result = $db->select($case)
            ->from($this->getConnection()->select(['column_name' => 2]))
            ->scalar();

        $this->assertEquals($expectedResult, $result);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'lengthBuilder')]
    public function testLengthBuilder(
        string|ExpressionInterface $operand,
        string $expectedSql,
        int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testLengthBuilder($operand, $expectedSql, $expectedResult, $expectedParams);

        $db = $this->getConnection();

        $length = new Length($operand);
        $result = $db->select($length)->scalar();

        $this->assertEquals($expectedResult, $result);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'multiOperandFunctionBuilder')]
    public function testMultiOperandFunctionBuilder(
        string $class,
        array $operands,
        string $expectedSql,
        array|string|int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testMultiOperandFunctionBuilder($class, $operands, $expectedSql, $expectedResult, $expectedParams);

        $db = $this->getConnection();

        $expression = new $class(...$operands);
        $result = $db->select($expression)->scalar();

        if (is_array($expectedResult)) {
            $arrayCol = $db->getColumnBuilderClass()::array();
            $result = $arrayCol->phpTypecast($result);
            sort($result, SORT_NATURAL);
        }

        $this->assertEquals($expectedResult, $result);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertWithMultiOperandFunctions')]
    public function testUpsertWithMultiOperandFunctions(
        array $initValues,
        array $insertValues,
        array $updateValues,
        string $expectedSql,
        array $expectedResult,
        array $expectedParams = [],
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();
        $schema = $db->getSchema();
        $command = $db->createCommand();

        $tableName = 'test_upsert_with_functions';

        if ($schema->hasTable($tableName)) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, [
            'id' => ColumnBuilder::primaryKey(false),
            'array_col' => ColumnBuilder::array(ColumnBuilder::integer()),
            'greatest_col' => ColumnBuilder::integer(),
            'least_col' => ColumnBuilder::integer(),
            'longest_col' => ColumnBuilder::string(),
            'shortest_col' => ColumnBuilder::string(),
        ])->execute();

        $command->insert($tableName, $initValues)->execute();

        $params = [];

        $sql = $qb->upsert($tableName, $insertValues, $updateValues, $params);

        $this->assertSame($expectedSql, $sql);
        $this->assertEquals($expectedParams, $params);

        $command->upsert($tableName, $insertValues, $updateValues, $params)->execute();

        $result = $db->select(array_keys($expectedResult))->from($tableName)->one();

        $this->assertEquals($expectedResult, $result);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'dateTimeValue')]
    public function testDateTimeValue(string $expected, string $column, DateTimeValue $expression): void
    {
        $db = $this->getConnection();
        $columnBuilder = $db->getColumnBuilderClass();

        try {
            $db->createCommand()->dropTable('date_time_value')->execute();
        } catch (Exception) {
            // Suppress exception if the table does not exist.
        }
        $dateColumn = $columnBuilder::date();
        $timeColumn = $columnBuilder::time();
        $timeTzColumn = $columnBuilder::timeWithTimezone();
        $dateTimeColumn = $columnBuilder::datetime();
        $dateTime3Column = $columnBuilder::datetime(3);
        $dateTimeTzColumn = $columnBuilder::datetimeWithTimezone();
        $timestampColumn = $columnBuilder::timestamp();
        $integerColumn = $columnBuilder::integer();
        $doubleColumn = $columnBuilder::double();
        $decimalColumn = $columnBuilder::decimal(16, 6);
        $db->createCommand()->createTable(
            'date_time_value',
            [
                'name' => $columnBuilder::string(),
                'date_col' => $dateColumn,
                'time_col' => $timeColumn,
                'timetz_col' => $timeTzColumn,
                'datetime_col' => $dateTimeColumn,
                'datetime3_col' => $dateTimeColumn,
                'datetimetz_col' => $dateTimeTzColumn,
                'timestamp_col' => $timestampColumn,
                'integer_col' => $integerColumn,
                'double_col' => $doubleColumn,
                'decimal_col' => $decimalColumn,
            ],
        )->execute();
        $date1 = new DateTimeImmutable('2025-08-21 15:30:45', new DateTimeZone('+03:00'));
        $date2 = new DateTimeImmutable('2023-03-19 11:25:00.12563', new DateTimeZone('UTC'));
        $db->createCommand()->insertBatch(
            'date_time_value',
            [
                [
                    'one',
                    $dateColumn->dbTypecast($date1),
                    $timeColumn->dbTypecast($date1),
                    $timeTzColumn->dbTypecast($date1),
                    $dateTimeColumn->dbTypecast($date1),
                    $dateTime3Column->dbTypecast($date1),
                    $dateTimeTzColumn->dbTypecast($date1),
                    $timestampColumn->dbTypecast($date1),
                    $integerColumn->dbTypecast($date1),
                    $doubleColumn->dbTypecast($date1),
                    $decimalColumn->dbTypecast($date1),
                ],
                [
                    'two',
                    $dateColumn->dbTypecast($date2),
                    $timeColumn->dbTypecast($date2),
                    $timeTzColumn->dbTypecast($date2),
                    $dateTimeColumn->dbTypecast($date2),
                    $dateTime3Column->dbTypecast($date2),
                    $dateTimeTzColumn->dbTypecast($date2),
                    $timestampColumn->dbTypecast($date2),
                    $integerColumn->dbTypecast($date2),
                    $doubleColumn->dbTypecast($date2),
                    $decimalColumn->dbTypecast($date2),
                ],
            ],
        )->execute();

        $query = $db
            ->select('name')
            ->from('date_time_value')
            ->where([$column => $expression]);
        $result = $query->column();

        $this->assertSame([$expected], $result, 'SQL Query: ' . $query->createCommand()->getRawSql());
    }
}
