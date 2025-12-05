<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\CollatableColumnInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredColumn;
use Yiisoft\Db\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Tests\Support\Stub\Column;

use function gettype;
use function is_object;

/**
 * @group db
 */
final class ColumnTest extends TestCase
{
    #[DataProviderExternal(ColumnProvider::class, 'predefinedTypes')]
    public function testPredefinedType(string $className, string $type)
    {
        /** @var ColumnInterface $column */
        $column = new $className();

        $this->assertSame($type, $column->getType());
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumns')]
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        // Set the timezone for testing purposes, could be any timezone except UTC
        $oldDatetime = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        foreach ($values as [$expected, $value]) {
            if (is_object($expected) && !(is_object($value) && $expected::class === $value::class)) {
                $this->assertEquals($expected, $column->dbTypecast($value));
            } else {
                $this->assertSame($expected, $column->dbTypecast($value));
            }
        }

        date_default_timezone_set($oldDatetime);
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumnsWithException')]
    public function testDbTypecastColumnsWithException(ColumnInterface $column, mixed $value)
    {
        $type = is_object($value) ? $value::class : gettype($value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Wrong $type value for {$column->getType()} column.");

        $column->dbTypecast($value);
    }

    #[DataProviderExternal(ColumnProvider::class, 'phpTypecastColumns')]
    public function testPhpTypecastColumns(ColumnInterface $column, array $values)
    {
        foreach ($values as [$expected, $value]) {
            if (is_object($expected) && !(is_object($value) && $expected::class === $value::class)) {
                $this->assertEquals($expected, $column->phpTypecast($value));
            } else {
                $this->assertSame($expected, $column->phpTypecast($value));
            }
        }
    }

    public function testAutoIncrement(): void
    {
        $column = new Column();

        $this->assertFalse($column->isAutoIncrement());
        $this->assertSame($column, $column->autoIncrement());
        $this->assertTrue($column->isAutoIncrement());

        $column->autoIncrement(false);

        $this->assertFalse($column->isAutoIncrement());

        $column->autoIncrement(true);

        $this->assertTrue($column->isAutoIncrement());
    }

    public function testCheck(): void
    {
        $column = new Column();

        $this->assertNull($column->getCheck());
        $this->assertSame($column, $column->check('age > 0'));
        $this->assertSame('age > 0', $column->getCheck());

        $column->check(null);

        $this->assertNull($column->getCheck());
    }

    public function testComment(): void
    {
        $column = new Column();

        $this->assertNull($column->getComment());
        $this->assertSame($column, $column->comment('test'));
        $this->assertSame('test', $column->getComment());

        $column->comment(null);

        $this->assertNull($column->getComment());
    }

    public function testComputed(): void
    {
        $column = new Column();

        $this->assertFalse($column->isComputed());
        $this->assertSame($column, $column->computed());
        $this->assertTrue($column->isComputed());

        $column->computed(false);

        $this->assertFalse($column->isComputed());

        $column->computed(true);

        $this->assertTrue($column->isComputed());
    }

    public function testDbType(): void
    {
        $column = new Column();

        $this->assertNull($column->getDbType());
        $this->assertSame($column, $column->dbType('test'));
        $this->assertSame('test', $column->getDbType());

        $column->dbType(null);

        $this->assertNull($column->getDbType());
    }

    public function testDefaultValue(): void
    {
        $column = new Column();

        $this->assertFalse($column->hasDefaultValue());
        $this->assertNull($column->getDefaultValue());
        $this->assertSame($column, $column->defaultValue('test'));
        $this->assertTrue($column->hasDefaultValue());
        $this->assertSame('test', $column->getDefaultValue());

        $column->defaultValue(null);

        $this->assertTrue($column->hasDefaultValue());
        $this->assertNull($column->getDefaultValue());
    }

    public function testExtra(): void
    {
        $column = new Column();

        $this->assertNull($column->getExtra());
        $this->assertSame($column, $column->extra('test'));
        $this->assertSame('test', $column->getExtra());

        $column->extra('');

        $this->assertSame('', $column->getExtra());
    }

    #[DataProviderExternal(ColumnProvider::class, 'construct')]
    public function testConstruct(string $parameter, mixed $value, string $method, mixed $expected): void
    {
        $column = new Column(...[$parameter => $value]);

        $this->assertSame($expected, $column->$method());
    }

    public function testName(): void
    {
        $column = new Column();

        $this->assertNull($column->getName());

        $newColumn = $column->withName('test');

        $this->assertNotSame($column, $newColumn);
        $this->assertSame('test', $newColumn->getName());

        $newColumn = $newColumn->withName('');

        $this->assertSame('', $newColumn->getName());
    }

    public function testNotNull(): void
    {
        $column = new Column();

        $this->assertNull($column->isNotNull());
        $this->assertSame($column, $column->notNull());
        $this->assertTrue($column->isNotNull());

        $column->notNull(false);

        $this->assertFalse($column->isNotNull());

        $column->notNull(null);

        $this->assertNull($column->isNotNull());

        $column->null();

        $this->assertFalse($column->isNotNull());
    }

    public function testPrimaryKey(): void
    {
        $column = new Column();

        $this->assertFalse($column->isPrimaryKey());
        $this->assertSame($column, $column->primaryKey());
        $this->assertTrue($column->isPrimaryKey());

        $column->primaryKey(false);

        $this->assertFalse($column->isPrimaryKey());

        $column->primaryKey(true);

        $this->assertTrue($column->isPrimaryKey());
    }

    public function testReference(): void
    {
        $column = new Column();
        $fk = new ForeignKey();

        $this->assertNull($column->getReference());
        $this->assertSame($column, $column->reference($fk));
        $this->assertSame($fk, $column->getReference());

        $column->reference(null);

        $this->assertNull($column->getReference());
    }

    public function testScale(): void
    {
        $column = new Column();

        $this->assertNull($column->getScale());
        $this->assertSame($column, $column->scale(10));
        $this->assertSame(10, $column->getScale());

        $column->scale(0);

        $this->assertSame(0, $column->getScale());
    }

    public function testSize(): void
    {
        $column = new Column();

        $this->assertNull($column->getSize());
        $this->assertSame($column, $column->size(10));
        $this->assertSame(10, $column->getSize());

        $column->size(0);

        $this->assertSame(0, $column->getSize());
    }

    public function testType(): void
    {
        $column = new Column();

        $this->assertSame('', $column->getType());
        $this->assertSame($column, $column->type('test'));
        $this->assertSame('test', $column->getType());

        $column->type('');

        $this->assertSame('', $column->getType());
    }

    public function testUnique(): void
    {
        $column = new Column();

        $this->assertFalse($column->isUnique());
        $this->assertSame($column, $column->unique());
        $this->assertTrue($column->isUnique());

        $column->unique(false);

        $this->assertFalse($column->isUnique());

        $column->unique(true);

        $this->assertTrue($column->isUnique());
    }

    public function testUnsigned(): void
    {
        $column = new Column();

        $this->assertFalse($column->isUnsigned());
        $this->assertSame($column, $column->unsigned());
        $this->assertTrue($column->isUnsigned());

        $column->unsigned(false);

        $this->assertFalse($column->isUnsigned());

        $column->unsigned();

        $this->assertTrue($column->isUnsigned());
    }

    public function testArrayColumnGetColumn(): void
    {
        $arrayCol = new ArrayColumn();
        $intCol = new IntegerColumn();

        $this->assertNull($arrayCol->getColumn());
        $this->assertSame($arrayCol, $arrayCol->column($intCol));
        $this->assertSame($intCol, $arrayCol->getColumn());

        $arrayCol->column(null);

        $this->assertNull($arrayCol->getColumn());
    }

    public function testArrayColumnGetDimension(): void
    {
        $arrayCol = new ArrayColumn();

        $this->assertSame(1, $arrayCol->getDimension());

        $arrayCol->dimension(2);
        $this->assertSame(2, $arrayCol->getDimension());
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::dbTypecastArrayColumns */
    public function testArrayColumnDbTypecast(ColumnInterface $column, array $values): void
    {
        $arrayCol = (new ArrayColumn())->column($column);

        foreach ($values as [$dimension, $expected, $value]) {
            $arrayCol->dimension($dimension);
            $dbValue = $arrayCol->dbTypecast($value);

            $this->assertInstanceOf(ArrayValue::class, $dbValue);
            $this->assertSame($arrayCol, $dbValue->type);
            $this->assertEquals($value, $dbValue->value);
        }
    }

    public function testStructuredColumnGetColumns(): void
    {
        $structuredCol = new StructuredColumn();
        $columns = [
            'value' => ColumnBuilder::money(),
            'currency_code' => ColumnBuilder::char(3),
        ];

        $this->assertSame([], $structuredCol->getColumns());
        $this->assertSame($structuredCol, $structuredCol->columns($columns));
        $this->assertSame($columns, $structuredCol->getColumns());
    }

    public function testStringColumnCollation(): void
    {
        $stringCol = new StringColumn();

        $this->assertInstanceOf(CollatableColumnInterface::class, $stringCol);
        $this->assertNull($stringCol->getCollation());
        $this->assertSame($stringCol, $stringCol->collation('utf8mb4'));
        $this->assertSame('utf8mb4', $stringCol->getCollation());
    }
}
