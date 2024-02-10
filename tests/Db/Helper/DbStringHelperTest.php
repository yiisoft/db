<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Helper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Helper\DbStringHelper;

/**
 * @group db
 */
final class DbStringHelperTest extends TestCase
{
    public function testBaseName(): void
    {
        $this->assertSame('TestCase', DbStringHelper::baseName(TestCase::class));
        $this->assertSame('TestCase', DbStringHelper::baseName('TestCase'));
    }

    public function testIsReadQuery(): void
    {
        $this->assertTrue(DbStringHelper::isReadQuery('SELECT * FROM tbl'));
        $this->assertTrue(DbStringHelper::isReadQuery('SELECT * FROM tbl WHERE id=1'));
        $this->assertTrue(DbStringHelper::isReadQuery('SELECT * FROM tbl WHERE id=1 LIMIT 1'));
        $this->assertTrue(DbStringHelper::isReadQuery('SELECT * FROM tbl WHERE id=1 LIMIT 1 OFFSET 1'));
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
        $this->assertEquals($expectedResult, DbStringHelper::pascalCaseToId($input));
    }

    public function testNormalizeFloat()
    {
        $this->assertSame('123', DbStringHelper::normalizeFloat(123));
        $this->assertSame('-123', DbStringHelper::normalizeFloat(-123));
        $this->assertSame('-2.5479E-70', DbStringHelper::normalizeFloat(-2.5479E-70));
        $this->assertSame('2.5479E-70', DbStringHelper::normalizeFloat(2.5479E-70));
        $this->assertSame('123.42', DbStringHelper::normalizeFloat(123.42));
        $this->assertSame('-123.42', DbStringHelper::normalizeFloat(-123.42));
        $this->assertSame('123.42', DbStringHelper::normalizeFloat('123.42'));
        $this->assertSame('-123.42', DbStringHelper::normalizeFloat('-123.42'));
        $this->assertSame('123.42', DbStringHelper::normalizeFloat('123,42'));
        $this->assertSame('-123.42', DbStringHelper::normalizeFloat('-123,42'));
        $this->assertSame('123123123.123', DbStringHelper::normalizeFloat('123.123.123,123'));
        $this->assertSame('123123123.123', DbStringHelper::normalizeFloat('123,123,123.123'));
        $this->assertSame('123123123.123', DbStringHelper::normalizeFloat('123 123 123,123'));
        $this->assertSame('123123123.123', DbStringHelper::normalizeFloat('123 123 123.123'));
        $this->assertSame('-123123123.123', DbStringHelper::normalizeFloat('-123 123 123.123'));
    }
}
