<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Provider\QueryBuilderProvider;

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

        $params = [];
        $qb->insertBatch('{{type}}', [$values], [], $params);

        $this->assertSame([
            ':qp0' => 1,
            ':qp1' => 'test',
            ':qp2' => 3.14,
            ':qp3' => $db->getDriverName() === 'oci' ? '1' : true,
        ], $params);

        $params = [];
        $qb->withTypecasting(false)->insertBatch('{{type}}', [$values], [], $params);

        $this->assertSame([
            ':qp0' => '1',
            ':qp1' => 'test',
            ':qp2' => '3.14',
            ':qp3' => '1',
        ], $params);

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
}
