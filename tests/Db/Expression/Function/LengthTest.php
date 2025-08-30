<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Function;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Tests\Support\TestTrait;

final class LengthTest extends TestCase
{
    use TestTrait;

    public static function dataOperands(): array
    {
        return [
            'expression' => ['expression'],
            'string' => [new Param('string', DataType::STRING)],
            'query' => [self::getDb()->select('column')->from('table')->where(['id' => 1])],
        ];
    }

    #[DataProvider('dataOperands')]
    public function testConstruct(string|ExpressionInterface $operand): void
    {
        $length = new Length($operand);

        $this->assertSame($operand, $length->operand);
    }
}
