<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\DataType;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Helper\DbUuidHelper;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_shift;

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

    public function testUuid(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        $tableName = '{{%column_schema_builder_types}}';
        if ($db->getTableSchema($tableName, true)) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'uuid_pk' => $schema->createColumn(PseudoType::UUID_PK),
            'int_col' => $schema->createColumn(ColumnType::INTEGER),
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
            ($builder->$method(...))(...$call);
        }

        $this->assertSame($expected, $builder->asString());

        $db->close();
    }
}
