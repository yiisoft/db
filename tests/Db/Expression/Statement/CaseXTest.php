<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Statement;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\Statement\WhenThen;
use Yiisoft\Db\Schema\Column\IntegerColumn;

/**
 * @group db
 */
final class CaseXTest extends TestCase
{
    public static function dataValues(): array
    {
        return [
            'null' => [null],
            'string' => ['field'],
            'expression' => [new Expression('field = 1')],
            'boolean' => [true],
            'float' => [2.3],
            'int' => [1],
            'array' => [['=', 'field', 1]],
        ];
    }

    #[DataProvider('dataValues')]
    public function testConstruct(mixed $value): void
    {
        $case = new CaseX($value, when: $whenThen = new WhenThen(1, 2));

        $this->assertSame($value, $case->value);
        $this->assertSame('', $case->valueType);
        $this->assertSame([$whenThen], $case->whenThen);
    }

    public function testConstructType(): void
    {
        $case = new CaseX(valueType: 'int', when: new WhenThen(1, 2));

        $this->assertNull($case->value);
        $this->assertSame('int', $case->valueType);

        $intCol = new IntegerColumn();
        $case = new CaseX(valueType: $intCol, when: new WhenThen(1, 2));
        $this->assertNull($case->value);
        $this->assertSame($intCol, $case->valueType);
    }

    public function testConstructorWhen()
    {
        // Test with one when clauses
        $whenThen = new WhenThen('value', 'result1');
        $case = new CaseX(when: $whenThen);

        $this->assertNull($case->value);
        $this->assertSame('', $case->valueType);
        $this->assertSame([$whenThen], $case->whenThen);

        // Test with multiple when clauses
        $whenThen = [
            'when0' => new WhenThen('value1', 'result1'),
            'when1' => new WhenThen('value2', 'result2'),
        ];
        $case = new CaseX(...$whenThen);

        $this->assertNull($case->value);
        $this->assertSame('', $case->valueType);
        $this->assertSame(array_values($whenThen), $case->whenThen);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`CASE` expression must have at least one `WHEN-THEN` clause.');

        new CaseX();
    }

    public function testElse(): void
    {
        $case = new CaseX(when: new WhenThen(1, 2));

        $this->assertFalse($case->hasElse());
        $this->assertFalse(isset($case->else));

        $case = new CaseX(when: new WhenThen(1, 2), else: null);

        $this->assertTrue($case->hasElse());
        $this->assertNull($case->else);

        $case = new CaseX(when: new WhenThen(1, 2), else: 'result');

        $this->assertTrue($case->hasElse());
        $this->assertSame('result', $case->else);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`CASE` expression can have only one `ELSE` value.');

        new CaseX(when: new WhenThen(1, 2), else1: 'result1', else2: 'result2');
    }

    public function testWhen(): void
    {
        $when = new WhenThen('value', 'result1');

        $this->assertSame('value', $when->when);
        $this->assertSame('result1', $when->then);
    }
}
