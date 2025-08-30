<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Function;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\ArrayExpression;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Least;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Expression\Function\Shortest;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Tests\Support\TestTrait;

final class MultiOperandFunctionTest extends TestCase
{
    use TestTrait;

    public static function dataOperands(): array
    {
        $stringValue = new Param('string', DataType::STRING);
        $query = self::getDb()->select('column')->from('table')->where(['id' => 1]);

        return [
            ArrayMerge::class => [ArrayMerge::class, [
                [[1, 2, 3]],
                [new ArrayExpression([1, 2, 3])],
                [$query],
                [[1, 2, 3], '[1,2,3]', new ArrayExpression([1, 2, 3]), $query],
            ]],
            Greatest::class => [Greatest::class, [
                [1],
                [1.5],
                ['1 + 2'],
                [$query],
                [1, 1.5, '1 + 2', $query],
            ]],
            Least::class => [Least::class, [
                [1],
                [1.5],
                ['1 + 2'],
                [$query],
                [1, 1.5, '1 + 2', $query],
            ]],
            Longest::class => [Longest::class, [
                ['expression'],
                [$stringValue],
                [$query],
                ['expression', $stringValue, $query],
            ]],
            Shortest::class => [Shortest::class, [
                ['expression'],
                [$stringValue],
                [$query],
                ['expression', $stringValue, $query],
            ]],
        ];
    }

    #[DataProvider('dataOperands')]
    public function testConstruct(string $class, array $operandLists): void
    {
        $expression = new $class();
        $this->assertInstanceOf(MultiOperandFunction::class, $expression);
        $this->assertSame([], $expression->getOperands());

        foreach ($operandLists as $operands) {
            $expression = new $class(...$operands);

            $this->assertSame($operands, $expression->getOperands());
        }
    }

    #[DataProvider('dataOperands')]
    public function testOperands(string $class, array $operandLists): void
    {
        foreach ($operandLists as $operands) {
            $expression = new $class();
            $this->assertSame([], $expression->getOperands());

            foreach ($operands as $operand) {
                $expression->add($operand);
            }

            $this->assertSame($operands, $expression->getOperands());
        }
    }
}
