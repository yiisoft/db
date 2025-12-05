<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Function;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Tests\Support\TestHelper;

final class LengthTest extends TestCase
{
    public static function dataOperands(): iterable
    {
        yield 'expression' => ['expression'];
        yield 'string' => [new Param('string', DataType::STRING)];

        $query = TestHelper::createSqliteMemoryConnection()->select('column')->from('table')->where(['id' => 1]);
        yield 'query' => [$query];
    }

    #[DataProvider('dataOperands')]
    public function testConstruct(string|ExpressionInterface $operand): void
    {
        $length = new Length($operand);

        $this->assertSame($operand, $length->operand);
    }
}
