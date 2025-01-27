<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredColumn;
use Yiisoft\Db\Tests\Support\Stub\Column;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnTest extends TestCase
{
    public function testAllowNull(): void
    {
        $column = new Column();

        $this->assertTrue($column->isAllowNull());
        $this->assertSame($column, $column->allowNull());
        $this->assertTrue($column->isAllowNull());

        $column->allowNull(false);

        $this->assertFalse($column->isAllowNull());

        $column->allowNull(true);

        $this->assertTrue($column->isAllowNull());
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

    public function testEnumValues(): void
    {
        $column = new Column();

        $this->assertNull($column->getEnumValues());
        $this->assertSame($column, $column->enumValues(['positive', 'negative']));
        $this->assertSame(['positive', 'negative'], $column->getEnumValues());

        $column->enumValues([]);

        $this->assertSame([], $column->getEnumValues());
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

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnProvider::construct */
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

    public function testPrecision(): void
    {
        $column = new Column();

        $this->assertNull($column->getPrecision());
        $this->assertSame($column, $column->precision(10));
        $this->assertSame(10, $column->getPrecision());

        $column->precision(0);

        $this->assertSame(0, $column->getPrecision());
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
        $fk = new ForeignKeyConstraint();

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

        $this->assertInstanceOf(StringColumn::class, $arrayCol->getColumn());
        $this->assertSame($arrayCol, $arrayCol->column($intCol));
        $this->assertSame($intCol, $arrayCol->getColumn());

        $arrayCol->column(null);

        $this->assertInstanceOf(StringColumn::class, $arrayCol->getColumn());
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
        $arrayCol = ColumnBuilder::array($column);

        foreach ($values as [$dimension, $expected, $value]) {
            $arrayCol->dimension($dimension);
            $dbValue = $arrayCol->dbTypecast($value);

            $this->assertInstanceOf(ArrayExpression::class, $dbValue);
            $this->assertSame($dimension, $dbValue->getDimension());

            $this->assertEquals($expected, $dbValue->getValue());
        }
    }

    public function testArrayColumnDbTypecastSimple()
    {
        $arrayCol = new ArrayColumn();

        $this->assertNull($arrayCol->dbTypecast(null));
        $this->assertEquals(new ArrayExpression([]), $arrayCol->dbTypecast(''));
        $this->assertEquals(new ArrayExpression([1, 2, 3]), $arrayCol->dbTypecast(new ArrayIterator([1, 2, 3])));
        $this->assertSame($expression = new Expression('expression'), $arrayCol->dbTypecast($expression));
    }

    public function testArrayColumnPhpTypecast()
    {
        $arrayCol = new ArrayColumn();

        $this->assertNull($arrayCol->phpTypecast(null));
        $this->assertNull($arrayCol->phpTypecast(1));
        $this->assertSame([], $arrayCol->phpTypecast([]));
        $this->assertSame(['1', '2', '3'], $arrayCol->phpTypecast(['1', '2', '3']));

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Schema\Column\ArrayColumn::getParser() is not supported. Use concrete DBMS implementation.'
        );

        $arrayCol->phpTypecast('{1,2,3}');
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

    public function testStructuredColumnDbTypecast(): void
    {
        $structuredCol = new StructuredColumn();
        $expression = new Expression('expression');
        $structuredExpression = new StructuredExpression(['value' => 1, 'currency_code' => 'USD']);

        $this->assertNull($structuredCol->dbTypecast(null));
        $this->assertSame($expression, $structuredCol->dbTypecast($expression));
        $this->assertSame($structuredExpression, $structuredCol->dbTypecast($structuredExpression));
        $this->assertEquals($structuredExpression, $structuredCol->dbTypecast(['value' => 1, 'currency_code' => 'USD']));
    }

    public function testStructuredColumnPhpTypecast(): void
    {
        $structuredCol = new StructuredColumn();
        $columns = [
            'int' => ColumnBuilder::integer(),
            'bool' => ColumnBuilder::boolean(),
        ];

        $this->assertNull($structuredCol->phpTypecast(null));
        $this->assertNull($structuredCol->phpTypecast(1));
        $this->assertSame(
            ['int' => '1', 'bool' => '1'],
            $structuredCol->phpTypecast(['int' => '1', 'bool' => '1'])
        );

        $structuredCol->columns($columns);
        $this->assertSame(
            ['int' => 1, 'bool' => true],
            $structuredCol->phpTypecast(['int' => '1', 'bool' => '1'])
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Schema\Column\StructuredColumn::getParser() is not supported. Use concrete DBMS implementation.'
        );

        $structuredCol->phpTypecast('(1,true)');
    }
}
