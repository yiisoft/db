<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Helper\StringHelper;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_shift;
use function call_user_func_array;

abstract class CommonColumnSchemaBuilderTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider::types
     */
    public function testCustomTypes(string $expected, string $type, int|null $length, array $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider::createColumnTypes
     */
    public function testCreateColumnTypes(string $expected, string $type, int|null $length, array $calls): void
    {
        $this->checkCreateColumn($expected, $type, $length, $calls);
    }

    public function testUuid(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        $tableName = '{{%column_schema_builder_types}}';
        if ($db->getTableSchema($tableName, true)) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'uuid_pk' => $schema->createColumn(SchemaInterface::TYPE_UUID_PK_SEQ),
            'int_col' => $schema->createColumn(SchemaInterface::TYPE_INTEGER),
        ])->execute();
        $tableSchema = $db->getTableSchema($tableName, true);
        $this->assertNotNull($tableSchema);

        $db->createCommand()->insert($tableName, ['int_col' => 1])->execute();

        $uuid = (new Query($db))
            ->select(['[[uuid_pk]]'])
            ->from($tableName)
            ->where(['int_col' => 1])
            ->scalar()
        ;

        $columnInfo = $tableSchema->getColumn('uuid_pk');

        $uuidString = StringHelper::toUuid($uuid);
        if ($columnInfo->getType() === SchemaInterface::TYPE_BINARY) {
            $this->assertEquals($uuid, StringHelper::uuidToBlob($uuidString));
        }

        $this->assertStringMatchesFormat('%s-%s-%s-%s-%s', $uuidString);
    }

    protected function checkBuildString(string $expected, string $type, int|null $length, array $calls): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $builder = $schema->createColumn($type, $length);

        foreach ($calls as $call) {
            $method = array_shift($call);
            call_user_func_array([$builder, $method], $call);
        }

        $this->assertSame($expected, $builder->asString());

        $db->close();
    }

    protected function checkCreateColumn(string $expected, string $type, int|null $length, array $calls): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $builder = $schema->createColumn($type, $length);

        foreach ($calls as $call) {
            $method = array_shift($call);
            call_user_func_array([$builder, $method], $call);
        }

        $tableName = '{{%column_schema_builder_types}}';
        if ($db->getTableSchema($tableName, true)) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $command = $db->createCommand()->createTable($tableName, [
            'column' => $builder,
        ]);

        $this->assertStringContainsString("\t" . $expected . "\n", $command->getRawSql());

        $command->execute();

        $tableSchema = $db->getTableSchema($tableName, true);
        $this->assertNotNull($tableSchema);

        $db->close();
    }
}
