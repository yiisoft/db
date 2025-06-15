<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Function\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Tests\Support\TestTrait;

class LengthBuilderTest extends TestCase
{
    use TestTrait;

    public static function dataBuild(): array
    {
        return [
            'expression' => [
                'hex(randomblob(16))',
                'LENGTH(hex(randomblob(16)))',
            ],
            'string param' => [
                $param = new Param('string', DataType::STRING),
                'LENGTH(:pv0)',
                [':pv0' => $param],
            ],
            'query' => [
                self::getDb()->select($param = new Param('value', DataType::STRING)),
                'LENGTH((SELECT :pv0))',
                [':pv0' => $param],
            ],
        ];
    }

    #[DataProvider('dataBuild')]
    public function testBuild(string|ExpressionInterface $operand, string $expected, array $expectedParams = []): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $length = new Length($operand);
        $params = [];

        $this->assertSame($expected, $qb->buildExpression($length, $params));
        $this->assertSame($expectedParams, $params);
    }
}
