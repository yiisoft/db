<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Tests\Support\Assert;

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

        $this->assertSame([
            ':qp0' => 1,
            ':qp1' => 'test',
            ':qp2' => 3.14,
            ':qp3' => $db->getDriverName() === 'oci' ? '1' : true,
        ], $params);

        $params = [];
        $qb->withTypecasting(false)->insert('{{type}}', $values, $params);

        $this->assertSame([
            ':qp0' => '1',
            ':qp1' => 'test',
            ':qp2' => '3.14',
            ':qp3' => '1',
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
        $qb->update('{{type}}', $values, [], $params);

        $this->assertSame([
            ':qp0' => 1,
            ':qp1' => 'test',
            ':qp2' => 3.14,
            ':qp3' => $db->getDriverName() === 'oci' ? '1' : true,
        ], $params);

        $params = [];
        $qb->withTypecasting(false)->update('{{type}}', $values, [], $params);

        $this->assertSame([
            ':qp0' => '1',
            ':qp1' => 'test',
            ':qp2' => '3.14',
            ':qp3' => '1',
        ], $params);
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
        string|int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testMultiOperandFunctionBuilder($class, $operands, $expectedSql, $expectedResult, $expectedParams);

        $db = $this->getConnection();

        $expression = new $class(...$operands);
        $result = $db->select($expression)->scalar();

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
}
