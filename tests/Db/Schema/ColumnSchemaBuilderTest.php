<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\Stub\Column;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaBuilderTest extends TestCase
{
    public function testAppend(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string bar', $column->append('bar')->asString());
        $this->assertSame('string foo', $column->append('foo')->asString());
    }

    public function testAppendWithEmptyString(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string', $column->append('')->asString());
    }

    public function testCheck(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string CHECK (value > 5)', $column->check('value > 5')->asString());
    }

    public function testCheckWithEmptyString(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string', $column->check('')->asString());
    }

    public function testCheckWithNull(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string', $column->check(null)->asString());
    }

    public function testComment(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string', $column->comment('comment')->asString());
    }

    public function testCommentWithEmptyString(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string', $column->comment('')->asString());
    }

    public function testCommentWithNull(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string', $column->comment(null)->asString());
    }

    public function testDefaultExpression(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame("string DEFAULT 'expression'", $column->defaultExpression("'expression'")->asString());
    }

    public function testDefaultExpressionWithEmptyString(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string DEFAULT ', $column->defaultExpression('')->asString());
    }

    public function testDefaultValue(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame("string DEFAULT ''value''", $column->defaultValue("'value'")->asString());
    }

    public function testDefaultValueWithEmptyString(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame("string DEFAULT ''", $column->defaultValue('')->asString());
    }

    public function testDefaultValueWithNull(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string NULL DEFAULT NULL', $column->defaultValue(null)->asString());
    }

    public function testGetAppend(): void
    {
        $column = new Column('string');

        $this->assertNull($column->getAppend());
        $this->assertSame('bar', $column->append('bar')->getAppend());
        $this->assertSame('bar', $column->getAppend());
    }

    public function testGetCategoryMap(): void
    {
        $column = new Column('string');

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
                'uuid' => 'uuid',
                'uuid_pk' => 'uuid_pk',
            ],
            $column->getCategoryMap(),
        );
    }

    public function testGetCheck(): void
    {
        $column = new Column('string');

        $this->assertNull($column->getCheck());
        $this->assertSame('value > 5', $column->check('value > 5')->getCheck());
        $this->assertSame('value > 5', $column->getCheck());
    }

    public function testGetComment(): void
    {
        $column = new Column('string');

        $this->assertNull($column->getComment());
        $this->assertSame('comment', $column->comment('comment')->getComment());
        $this->assertSame('comment', $column->getComment());
    }

    public function testGetDefault(): void
    {
        $column = new Column('string');

        $this->assertNull($column->getDefault());
        $this->assertSame("'value'", $column->defaultValue("'value'")->getDefault());
        $this->assertSame("'value'", $column->getDefault());
    }

    public function testGetDefaultExpression(): void
    {
        $column = new Column('string');

        $this->assertNull($column->getDefault());
        $this->assertInstanceOf(Expression::class, $column->defaultExpression("'expression'")->getDefault());
        $this->assertInstanceOf(Expression::class, $column->getDefault());
    }

    public function testGetLength(): void
    {
        $column = new Column('string', 10);

        $this->assertSame(10, $column->getLength());
    }

    public function testIsNotNull(): void
    {
        $column = new Column('string');

        $this->assertNull($column->isNotNull());
        $this->assertTrue($column->notNull()->isNotNull());
    }

    public function testIsUnique(): void
    {
        $column = new Column('string');

        $this->assertFalse($column->isUnique());
        $this->assertTrue($column->unique()->isUnique());
    }

    public function testIsUnsigned(): void
    {
        $column = new Column('pk');

        $this->assertFalse($column->isUnsigned());
        $this->assertTrue($column->unsigned()->isUnsigned());
    }

    public function testLengthWithArray(): void
    {
        $column = new Column('integer', [10, 2]);

        $this->assertSame('integer(10,2)', $column->asString());
    }

    public function testNotnull(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string NOT NULL', $column->notNull()->asString());
    }

    public function testNull(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string NULL DEFAULT NULL', $column->null()->asString());
    }

    public function testUnique(): void
    {
        $column = new Column('string');

        $this->assertSame('string', $column->asString());
        $this->assertSame('string UNIQUE', $column->unique()->asString());
    }

    public function testUnsignedTypePk(): void
    {
        $column = new Column(SchemaInterface::TYPE_PK);

        $this->assertSame('pk', $column->asString());
        $this->assertSame('upk', $column->unsigned()->asString());
    }

    public function testUnsignedTypeUbigPk(): void
    {
        $column = new Column(SchemaInterface::TYPE_BIGPK);

        $this->assertSame('bigpk', $column->asString());
        $this->assertSame('ubigpk', $column->unsigned()->asString());
    }
}
