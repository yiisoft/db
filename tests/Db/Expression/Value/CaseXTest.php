<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\Statement\When;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class CaseXTest extends TestCase
{
    use TestTrait;

    public static function dataValues(): array
    {
        return [
            'null' => [null],
            'string' => ['field = 1'],
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
        $case = new CaseX($value, when: $when = new When(1, 2));

        $this->assertSame($value, $case->value);
        $this->assertSame('', $case->valueType);
        $this->assertSame([$when], $case->when);
    }

    public function testConstructType(): void
    {
        $case = new CaseX(valueType: 'int', when: new When(1, 2));

        $this->assertNull($case->value);
        $this->assertSame('int', $case->valueType);

        $intCol = new IntegerColumn();
        $case = new CaseX(valueType: $intCol, when: new When(1, 2));
        $this->assertNull($case->value);
        $this->assertSame($intCol, $case->valueType);
    }

    public function testConstructorWhen()
    {
        // Test with one when clauses
        $when = new When('field = 1', 'result1');
        $case = new CaseX(when: $when);

        $this->assertNull($case->value);
        $this->assertSame('', $case->valueType);
        $this->assertSame([$when], $case->when);

        // Test with multiple when clauses
        $when = [
            'when0' => new When('field = 1', 'result1'),
            'when1' => new When('field = 2', 'result2'),
        ];
        $case = new CaseX(...$when);

        $this->assertNull($case->value);
        $this->assertSame('', $case->valueType);
        $this->assertSame(array_values($when), $case->when);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`CASE` expression must have at least one `WHEN` clause.');

        new CaseX();
    }

    public function testElse(): void
    {
        $case = new CaseX(when: new When(1, 2));

        $this->assertFalse($case->hasElse());
        $this->assertFalse(isset($case->else));

        $case = new CaseX(when: new When(1, 2), else: null);

        $this->assertTrue($case->hasElse());
        $this->assertNull($case->else);

        $case = new CaseX(when: new When(1, 2), else: 'result');

        $this->assertTrue($case->hasElse());
        $this->assertSame('result', $case->else);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`CASE` expression can have only one `ELSE` value.');

        new CaseX(when: new When(1, 2), else1: 'result1', else2: 'result2');
    }

    public function testWhen(): void
    {
        $when = new When('field = 1', 'result1');

        $this->assertSame('field = 1', $when->condition);
        $this->assertSame('result1', $when->result);
    }
}
