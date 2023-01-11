<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaBuilderTest extends TestCase
{
    public function testAppend(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string bar', (string) $column->append('bar'));
        $this->assertSame('string foo', (string) $column->append('foo'));
    }

    public function testAppendWithEmptyString(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string', (string) $column->append(''));
    }

    public function testCheck(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string CHECK (value > 5)', (string) $column->check('value > 5'));
    }

    public function testCheckWithEmptyString(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string', (string) $column->check(''));
    }

    public function testCheckWithNull(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string', (string) $column->check(null));
    }

    public function testComment(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string', (string) $column->comment('comment'));
    }

    public function testCommentWithEmptyString(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string', (string) $column->comment(''));
    }

    public function testCommentWithNull(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string', (string) $column->comment(null));
    }

    public function testDefaultExpression(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame("string DEFAULT 'expression'", (string) $column->defaultExpression("'expression'"));
    }

    public function testDefaultExpressionWithEmptyString(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string DEFAULT ', (string) $column->defaultExpression(''));
    }

    public function testDefaultValue(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame("string DEFAULT ''value''", (string) $column->defaultValue("'value'"));
    }

    public function testDefaultValueWithEmptyString(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame("string DEFAULT ''", (string) $column->defaultValue(''));
    }

    public function testDefaultValueWithNull(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string NULL DEFAULT NULL', (string) $column->defaultValue(null));
    }

    public function testGetAppend(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertNull($column->getAppend());
        $this->assertSame('bar', $column->append('bar')->getAppend());
        $this->assertSame('bar', $column->getAppend());
    }

    public function testGetCategoryMap(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame(
            [
                'pk' => 'pk',
                'upk' => 'pk',
                'bigpk' => 'pk',
                'ubigpk' => 'pk',
                'char' => 'string',
                'string' => 'string',
                'text' => 'string',
                'tinyint' => 'numeric',
                'smallint' => 'numeric',
                'integer' => 'numeric',
                'bigint' => 'numeric',
                'float' => 'numeric',
                'double' => 'numeric',
                'decimal' => 'numeric',
                'datetime' => 'time',
                'timestamp' => 'time',
                'time' => 'time',
                'date' => 'time',
                'binary' => 'other',
                'boolean' => 'numeric',
                'money' => 'numeric',
            ],
            $column->getCategoryMap(),
        );
    }

    public function testGetCheck(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertNull($column->getCheck());
        $this->assertSame('value > 5', $column->check('value > 5')->getCheck());
        $this->assertSame('value > 5', $column->getCheck());
    }

    public function testGetComment(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertNull($column->getComment());
        $this->assertSame('comment', $column->comment('comment')->getComment());
        $this->assertSame('comment', $column->getComment());
    }

    public function testGetDefault(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertNull($column->getDefault());
        $this->assertSame("'value'", $column->defaultValue("'value'")->getDefault());
        $this->assertSame("'value'", $column->getDefault());
    }

    public function testGetDefaultExpression(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertNull($column->getDefault());
        $this->assertInstanceOf(Expression::class, $column->defaultExpression("'expression'")->getDefault());
        $this->assertInstanceOf(Expression::class, $column->getDefault());
    }

    public function testGetLength(): void
    {
        $column = new ColumnSchemaBuilder('string', 10);

        $this->assertSame(10, $column->getLength());
    }

    public function testIsNotNull(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertNull($column->isNotNull());
        $this->assertTrue($column->notNull()->isNotNull());
    }

    public function testIsUnique(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertFalse($column->isUnique());
        $this->assertTrue($column->unique()->isUnique());
    }

    public function testIsUnsigned(): void
    {
        $column = new ColumnSchemaBuilder('pk');

        $this->assertFalse($column->isUnsigned());
        $this->assertTrue($column->unsigned()->isUnsigned());
    }

    public function testLengthWithArray(): void
    {
        $column = new ColumnSchemaBuilder('integer', [10, 2]);

        $this->assertSame('integer(10,2)', (string) $column);
    }

    public function testNotnull(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string NOT NULL', (string) $column->notNull());
    }

    public function testNull(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string NULL DEFAULT NULL', (string) $column->null());
    }

    public function testUnique(): void
    {
        $column = new ColumnSchemaBuilder('string');

        $this->assertSame('string', (string) $column);
        $this->assertSame('string UNIQUE', (string) $column->unique());
    }

    public function testUnsignedTypePk(): void
    {
        $column = new ColumnSchemaBuilder(SchemaInterface::TYPE_PK);

        $this->assertSame('pk', (string) $column);
        $this->assertSame('upk', (string) $column->unsigned());
    }

    public function testUnsignedTypeUbigPk(): void
    {
        $column = new ColumnSchemaBuilder(SchemaInterface::TYPE_BIGPK);

        $this->assertSame('bigpk', (string) $column);
        $this->assertSame('ubigpk', (string) $column->unsigned());
    }
}
