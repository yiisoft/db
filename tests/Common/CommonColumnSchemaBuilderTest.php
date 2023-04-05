<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Helper\DbUuidHelper;
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
            'uuid_pk' => $schema->createColumn(SchemaInterface::TYPE_UUID_PK),
            'int_col' => $schema->createColumn(SchemaInterface::TYPE_INTEGER),
        ])->execute();
        $tableSchema = $db->getTableSchema($tableName, true);
        $this->assertNotNull($tableSchema);

        $uuidValue = $uuidSource = '738146be-87b1-49f2-9913-36142fb6fcbe';

        if ($db->getDriverName() === 'oci') {
            $uuidValue = new Expression('HEXTORAW(REGEXP_REPLACE(:uuid, \'-\', \'\'))', [':uuid' => $uuidValue]);
        } elseif ($db->getDriverName() === 'mysql') {
            $uuidValue = DbUuidHelper::uuidToBlob($uuidValue);
        }

        $db->createCommand()->insert($tableName, [
            'int_col' => 1,
            'uuid_pk' => $uuidValue,
        ])->execute();

        $uuid = (new Query($db))
            ->select(['[[uuid_pk]]'])
            ->from($tableName)
            ->where(['int_col' => 1])
            ->scalar()
        ;

        $uuidString = strtolower(DbUuidHelper::toUuid($uuid));

        $this->assertEquals($uuidSource, $uuidString);

        $db->close();
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

        if (str_contains($expected, 'UUID_TO_BIN')) {
            $serverVersion = $db->getServerVersion();
            if (str_contains($serverVersion, 'MariaDB')) {
                $db->close();
                $this->markTestSkipped('UUID_TO_BIN not supported MariaDB as defaultValue');
            }
            if (version_compare($serverVersion, '8', '<')) {
                $db->close();
                $this->markTestSkipped('UUID_TO_BIN not exists in MySQL 5.7');
            }
        }

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
