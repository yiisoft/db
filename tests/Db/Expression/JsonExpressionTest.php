<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\TestTrait;

use function json_encode;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class JsonExpressionTest extends TestCase
{
    use TestTrait;

    public function testConstruct(): void
    {
        $expression = new JsonExpression(['a', 'b', 'c'], 'string');

        $this->assertSame(['a', 'b', 'c'], $expression->getValue());
        $this->assertSame('string', $expression->getType());
    }

    public function testConstructValueIsJsonExpression(): void
    {
        $expression = new JsonExpression(['a', 'b', 'c'], 'string');
        $expression2 = new JsonExpression($expression, 'string');

        $this->assertSame(['a', 'b', 'c'], $expression2->getValue());
        $this->assertSame('string', $expression2->getType());
    }

    public function testJsonSerialize(): void
    {
        $expression = new JsonExpression(['a', 'b', 'c'], 'string');

        $this->assertSame('["a","b","c"]', json_encode($expression->jsonSerialize(), JSON_THROW_ON_ERROR));
    }

    public function testJsonSerializeQueryInterfaceException(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->select(['a', 'b', 'c']);
        $expression = new JsonExpression($query, 'string');

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'The JsonExpression class can not be serialized to JSON when the value is a QueryInterface object.'
        );

        $expression->jsonSerialize();
    }
}
