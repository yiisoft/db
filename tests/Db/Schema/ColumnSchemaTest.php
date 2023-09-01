<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\Column\BigIntColumnSchema;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\Stub\ColumnSchema;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaTest extends TestCase
{
    public function testAllowNull(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isAllowNull());

        $column->allowNull(true);

        $this->assertTrue($column->isAllowNull());

        $column->allowNull(false);

        $this->assertFalse($column->isAllowNull());
    }

    public function testAutoIncrement(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isAutoIncrement());

        $column->autoIncrement(true);

        $this->assertTrue($column->isAutoIncrement());

        $column->autoIncrement(false);

        $this->assertFalse($column->isAutoIncrement());
    }

    public function testComment(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getComment());

        $column->comment('test');

        $this->assertSame('test', $column->getComment());

        $column->comment(null);

        $this->assertNull($column->getComment());
    }

    public function testComputed(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isComputed());

        $column->computed(true);

        $this->assertTrue($column->isComputed());

        $column->computed(false);

        $this->assertFalse($column->isComputed());
    }

    public function testDbType(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getDbType());

        $column->dbType('test');

        $this->assertSame('test', $column->getDbType());

        $column->dbType(null);

        $this->assertNull($column->getDbType());
    }

    public function testDbTypecast(): void
    {
        $column = new ColumnSchema('new');

        $this->assertSame('', $column->dbTypecast(''));
    }

    public function testDefaultValue(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getDefaultValue());

        $column->defaultValue('test');

        $this->assertSame('test', $column->getDefaultValue());

        $column->defaultValue(null);

        $this->assertNull($column->getDefaultValue());
    }

    public function testEnumValues(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getEnumValues());

        $column->enumValues(['positive', 'negative']);

        $this->assertSame(['positive', 'negative'], $column->getEnumValues());

        $column->enumValues([]);

        $this->assertSame([], $column->getEnumValues());
    }

    public function testExtra(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getExtra());

        $column->extra('test');

        $this->assertSame('test', $column->getExtra());

        $column->extra('');

        $this->assertSame('', $column->getExtra());
    }

    /**
     * @link https://github.com/yiisoft/db/issues/718
     */
    public function testTypecastIssue718(): void
    {
        $column = new ColumnSchema('new');

        $param = [1, 2];
        $result = $column->dbTypecast($param);
        $this->assertSame([1, 2], $result);
    }

    public function testName(): void
    {
        $column = new ColumnSchema('test');

        $this->assertSame('test', $column->getName());
    }

    public function testPhpType(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getPhpType());

        $column->phpType(SchemaInterface::PHP_TYPE_STRING);

        $this->assertSame(SchemaInterface::PHP_TYPE_STRING, $column->getPhpType());

        $column->phpType(null);

        $this->assertNull($column->getPhpType());
    }

    public function testPhpTypecast(): void
    {
        $column = new StringColumnSchema('new');

        $this->assertSame('test', $column->phpTypecast('test'));
    }

    public function testPhpTypecastWithBoolean(): void
    {
        $column = new BooleanColumnSchema('new');

        $this->assertTrue($column->phpTypecast(1));
    }

    public function testPhpTypecastWithDouble(): void
    {
        $column = new DoubleColumnSchema('new');

        $this->assertSame(1.2, $column->phpTypecast('1.2'));
    }

    public function testPhpTypecastWithInteger(): void
    {
        $column = new IntegerColumnSchema('new');

        $this->assertSame(1, $column->phpTypecast('1'));
    }

    public function testPhpTypecastWithStringBooleanValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return bool value for string type');

        $column = new StringColumnSchema('new');

        $this->assertSame('1', $column->phpTypecast(true));
    }

    public function testPhpTypecastWithStringFloatValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return double value for string type');

        $column = new StringColumnSchema('new');

        $this->assertSame('1.1', $column->phpTypecast(1.1));
    }

    public function testPhpTypecastWithStringIntegerValue(): void
    {
        self::markTestSkipped('Wrong test: database does not return int value for string type');

        $column = new StringColumnSchema('new');

        $this->assertSame('1', $column->phpTypecast(1));
    }

    public function testPhpTypecastWithStringNullValue(): void
    {
        $column = new StringColumnSchema('new');

        $this->assertNull($column->phpTypecast(null));
    }

    public function testPhpTypecastWithStringResourceValue(): void
    {
        $column = new StringColumnSchema('new');

        $this->assertIsResource($column->phpTypecast(fopen('php://memory', 'rb')));
    }

    public function testPrecision(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getPrecision());

        $column->precision(10);

        $this->assertSame(10, $column->getPrecision());

        $column->precision(0);

        $this->assertSame(0, $column->getPrecision());
    }

    public function testPrimaryKey(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isPrimaryKey());

        $column->primaryKey(true);

        $this->assertTrue($column->isPrimaryKey());

        $column->primaryKey(false);

        $this->assertFalse($column->isPrimaryKey());
    }

    public function testScale(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getScale());

        $column->scale(10);

        $this->assertSame(10, $column->getScale());

        $column->scale(0);

        $this->assertSame(0, $column->getScale());
    }

    public function testSize(): void
    {
        $column = new ColumnSchema('new');

        $this->assertNull($column->getSize());

        $column->size(10);

        $this->assertSame(10, $column->getSize());

        $column->size(0);

        $this->assertSame(0, $column->getSize());
    }

    public function testType(): void
    {
        $column = new ColumnSchema('new');

        $this->assertSame('', $column->getType());

        $column->type('test');

        $this->assertSame('test', $column->getType());

        $column->type('');

        $this->assertSame('', $column->getType());
    }

    public function testUnsigned(): void
    {
        $column = new ColumnSchema('new');

        $this->assertFalse($column->isUnsigned());

        $column->unsigned(true);

        $this->assertTrue($column->isUnsigned());

        $column->unsigned(false);

        $this->assertFalse($column->isUnsigned());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnSchemaProvider::dbTypecastColumns
     */
    public function testDbTypecastColumnSchema(array $columns)
    {
        foreach ($columns as $class => $values) {
            $col = new $class('column_name');

            foreach ($values as [$expected, $value]) {
                if (is_object($expected) && !(is_object($value) && $expected::class === $value::class)) {
                    $this->assertEquals($expected, $col->dbTypecast($value));
                } else {
                    $this->assertSame($expected, $col->dbTypecast($value));
                }
            }
        }
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnSchemaProvider::phpTypecastColumns
     */
    public function testPhpTypecastColumnSchema(array $columns)
    {
        foreach ($columns as $class => $values) {
            $col = new $class('column_name');

            foreach ($values as [$expected, $value]) {
                $this->assertSame($expected, $col->phpTypecast($value));
            }
        }
    }

    public function testIntegerColumnSchema()
    {
        $intCol = new IntegerColumnSchema('int_col');

        $this->assertSame('int_col', $intCol->getName());
        $this->assertSame(SchemaInterface::TYPE_INTEGER, $intCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_INTEGER, $intCol->getPhpType());

        $this->assertNull($intCol->dbTypecast(null));
        $this->assertNull($intCol->dbTypecast(''));
        $this->assertSame(1, $intCol->dbTypecast(1));
        $this->assertSame(1, $intCol->dbTypecast(1.0));
        $this->assertSame(1, $intCol->dbTypecast('1'));
        $this->assertSame(1, $intCol->dbTypecast(true));
        $this->assertSame(0, $intCol->dbTypecast(false));
        $this->assertEquals(new Expression('1'), $intCol->dbTypecast(new Expression('1')));

        $this->assertNull($intCol->phpTypecast(null));
        $this->assertSame(1, $intCol->phpTypecast(1));
        $this->assertSame(1, $intCol->phpTypecast('1'));
    }

    public function testBigIntColumnSchema()
    {
        $bigintCol = new BigIntColumnSchema('bigint_col');

        $this->assertSame('bigint_col', $bigintCol->getName());
        $this->assertSame(SchemaInterface::TYPE_BIGINT, $bigintCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_INTEGER, $bigintCol->getPhpType());

        $this->assertNull($bigintCol->dbTypecast(null));
        $this->assertNull($bigintCol->dbTypecast(''));
        $this->assertSame(1, $bigintCol->dbTypecast(1));
        $this->assertSame(1, $bigintCol->dbTypecast(1.0));
        $this->assertSame(1, $bigintCol->dbTypecast('1'));
        $this->assertSame(1, $bigintCol->dbTypecast(true));
        $this->assertSame(0, $bigintCol->dbTypecast(false));
        $this->assertSame('12345678901234567890', $bigintCol->dbTypecast('12345678901234567890'));
        $this->assertEquals(new Expression('1'), $bigintCol->dbTypecast(new Expression('1')));

        $this->assertNull($bigintCol->phpTypecast(null));
        $this->assertSame(1, $bigintCol->phpTypecast(1));
        $this->assertSame(1, $bigintCol->phpTypecast('1'));
        $this->assertSame('12345678901234567890', $bigintCol->phpTypecast('12345678901234567890'));
    }

    public function testDoubleColumnSchema()
    {
        $floatCol = new DoubleColumnSchema('float_col');

        $this->assertSame('float_col', $floatCol->getName());
        $this->assertSame(SchemaInterface::TYPE_DOUBLE, $floatCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_DOUBLE, $floatCol->getPhpType());

        $this->assertNull($floatCol->dbTypecast(null));
        $this->assertNull($floatCol->dbTypecast(''));
        $this->assertSame(1.0, $floatCol->dbTypecast(1));
        $this->assertSame(1.0, $floatCol->dbTypecast(1.0));
        $this->assertSame(1.0, $floatCol->dbTypecast('1'));
        $this->assertSame(1.0, $floatCol->dbTypecast(true));
        $this->assertSame(0.0, $floatCol->dbTypecast(false));
        $this->assertEquals(new Expression('1'), $floatCol->dbTypecast(new Expression('1')));

        $this->assertNull($floatCol->phpTypecast(null));
        $this->assertSame(1.0, $floatCol->phpTypecast(1.0));
        $this->assertSame(1.0, $floatCol->phpTypecast('1.0'));
    }

    public function testStringColumnSchema()
    {
        $stringCol = new StringColumnSchema('string_col');

        $this->assertSame('string_col', $stringCol->getName());
        $this->assertSame(SchemaInterface::TYPE_STRING, $stringCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_STRING, $stringCol->getPhpType());

        $this->assertNull($stringCol->dbTypecast(null));
        $this->assertSame('1', $stringCol->dbTypecast(1));
        $this->assertSame('1', $stringCol->dbTypecast(true));
        $this->assertSame('0', $stringCol->dbTypecast(false));
        $this->assertSame('string', $stringCol->dbTypecast('string'));
        $this->assertIsResource($stringCol->dbTypecast(fopen('php://memory', 'rb')));
        $this->assertEquals(new Expression('expression'), $stringCol->dbTypecast(new Expression('expression')));

        $this->assertNull($stringCol->phpTypecast(null));
        $this->assertSame('string', $stringCol->phpTypecast('string'));
        $this->assertIsResource($stringCol->phpTypecast(fopen('php://memory', 'rb')));
    }

    public function testBinaryColumnSchema()
    {
        $binaryCol = new BinaryColumnSchema('binary_col');

        $this->assertSame('binary_col', $binaryCol->getName());
        $this->assertSame(SchemaInterface::TYPE_BINARY, $binaryCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_RESOURCE, $binaryCol->getPhpType());

        $this->assertNull($binaryCol->dbTypecast(null));
        $this->assertSame('1', $binaryCol->dbTypecast(1));
        $this->assertSame('1', $binaryCol->dbTypecast(true));
        $this->assertSame('0', $binaryCol->dbTypecast(false));
        $this->assertIsResource($binaryCol->dbTypecast(fopen('php://memory', 'rb')));
        $this->assertEquals(new Param("\x10\x11\x12", PDO::PARAM_LOB), $binaryCol->dbTypecast("\x10\x11\x12"));
        $this->assertEquals(new Expression('expression'), $binaryCol->dbTypecast(new Expression('expression')));

        $this->assertNull($binaryCol->phpTypecast(null));
        $this->assertSame("\x10\x11\x12", $binaryCol->phpTypecast("\x10\x11\x12"));
        $this->assertIsResource($binaryCol->phpTypecast(fopen('php://memory', 'rb')));
    }

    public function testBooleanColumnSchema()
    {
        $boolCol = new BooleanColumnSchema('bool_col');

        $this->assertSame('bool_col', $boolCol->getName());
        $this->assertSame(SchemaInterface::TYPE_BOOLEAN, $boolCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_BOOLEAN, $boolCol->getPhpType());

        $this->assertNull($boolCol->dbTypecast(null));
        $this->assertNull($boolCol->dbTypecast(''));
        $this->assertTrue($boolCol->dbTypecast(true));
        $this->assertTrue($boolCol->dbTypecast(1));
        $this->assertTrue($boolCol->dbTypecast(1.0));
        $this->assertTrue($boolCol->dbTypecast('1'));
        $this->assertFalse($boolCol->dbTypecast(false));
        $this->assertFalse($boolCol->dbTypecast(0));
        $this->assertFalse($boolCol->dbTypecast('0'));
        $this->assertEquals(new Expression('expression'), $boolCol->dbTypecast(new Expression('expression')));

        $this->assertNull($boolCol->phpTypecast(null));
        $this->assertTrue($boolCol->phpTypecast(true));
        $this->assertTrue($boolCol->phpTypecast('1'));
        $this->assertFalse($boolCol->phpTypecast(false));
        $this->assertFalse($boolCol->phpTypecast('0'));
        $this->assertFalse($boolCol->phpTypecast("\0"));
    }

    public function testJsonColumnSchema()
    {
        $jsonCol = new JsonColumnSchema('json_col');

        $this->assertSame('json_col', $jsonCol->getName());
        $this->assertSame(SchemaInterface::TYPE_JSON, $jsonCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_ARRAY, $jsonCol->getPhpType());

        $this->assertNull($jsonCol->dbTypecast(null));
        $this->assertEquals(new JsonExpression(1, 'json'), $jsonCol->dbTypecast(1));
        $this->assertEquals(new JsonExpression(true, 'json'), $jsonCol->dbTypecast(true));
        $this->assertEquals(new JsonExpression(false, 'json'), $jsonCol->dbTypecast(false));
        $this->assertEquals(new JsonExpression('string', 'json'), $jsonCol->dbTypecast('string'));
        $this->assertEquals(new JsonExpression([1, 2, 3], 'json'), $jsonCol->dbTypecast([1, 2, 3]));
        $this->assertEquals(new JsonExpression(['key' => 'value'], 'json'), $jsonCol->dbTypecast(['key' => 'value']));
        $this->assertEquals(new JsonExpression(new stdClass(), 'json'), $jsonCol->dbTypecast(new stdClass()));
        $this->assertEquals(new JsonExpression([1, 2, 3]), $jsonCol->dbTypecast(new JsonExpression([1, 2, 3])));

        $this->assertNull($jsonCol->phpTypecast(null));
        $this->assertSame(1.0, $jsonCol->phpTypecast('1.0'));
        $this->assertSame(1, $jsonCol->phpTypecast('1'));
        $this->assertTrue($jsonCol->phpTypecast('true'));
        $this->assertFalse($jsonCol->phpTypecast('false'));
        $this->assertSame('string', $jsonCol->phpTypecast('"string"'));
        $this->assertSame([1, 2, 3], $jsonCol->phpTypecast('[1,2,3]'));
        $this->assertSame(['key' => 'value'], $jsonCol->phpTypecast('{"key":"value"}'));
    }
}
