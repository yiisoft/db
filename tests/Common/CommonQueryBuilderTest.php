<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\CaseExpression;
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

    #[DataProviderExternal(QueryBuilderProvider::class, 'caseExpressionBuilder')]
    public function testCaseExpressionBuilder(
        CaseExpression $case,
        string $expectedSql,
        array $expectedParams,
        string|int $expectedResult,
    ): void {
        parent::testCaseExpressionBuilder($case, $expectedSql, $expectedParams, $expectedResult);

        $result = $this->getConnection()
            ->select($case)
            ->from($this->getConnection()->select(['column_name' => 2]))
            ->scalar();

        $this->assertEquals($expectedResult, $result);
    }
}
