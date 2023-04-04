<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Helper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Helper\StringHelper;

/**
 * @group db
 */
final class StringHelperTest extends TestCase
{
    public function testBaseName(): void
    {
        $this->assertSame('TestCase', StringHelper::baseName('PHPUnit\Framework\TestCase'));
        $this->assertSame('TestCase', StringHelper::baseName('TestCase'));
    }

    public static function pascalCaseToIdProvider(): array
    {
        return [
            ['photo\\album_controller', 'Photo\\AlbumController',],
            ['photo\\album\\controller', 'Photo\\Album\\Controller',],
            ['post_tag', 'PostTag',],
            ['post_tag', 'postTag'],
            ['foo_ybar', 'fooYBar',],
        ];
    }

    /**
     * @dataProvider pascalCaseToIdProvider
     */
    public function testPascalCaseToId(string $expectedResult, string $input): void
    {
        $this->assertEquals($expectedResult, StringHelper::pascalCaseToId($input));
    }

    public function testNormalizeFloat()
    {
        $this->assertSame('123', StringHelper::normalizeFloat(123));
        $this->assertSame('-123', StringHelper::normalizeFloat(-123));
        $this->assertSame('-2.5479E-70', StringHelper::normalizeFloat(-2.5479E-70));
        $this->assertSame('2.5479E-70', StringHelper::normalizeFloat(2.5479E-70));
        $this->assertSame('123.42', StringHelper::normalizeFloat(123.42));
        $this->assertSame('-123.42', StringHelper::normalizeFloat(-123.42));
        $this->assertSame('123.42', StringHelper::normalizeFloat('123.42'));
        $this->assertSame('-123.42', StringHelper::normalizeFloat('-123.42'));
        $this->assertSame('123.42', StringHelper::normalizeFloat('123,42'));
        $this->assertSame('-123.42', StringHelper::normalizeFloat('-123,42'));
        $this->assertSame('123123123.123', StringHelper::normalizeFloat('123.123.123,123'));
        $this->assertSame('123123123.123', StringHelper::normalizeFloat('123,123,123.123'));
        $this->assertSame('123123123.123', StringHelper::normalizeFloat('123 123 123,123'));
        $this->assertSame('123123123.123', StringHelper::normalizeFloat('123 123 123.123'));
        $this->assertSame('-123123123.123', StringHelper::normalizeFloat('-123 123 123.123'));
    }
}
