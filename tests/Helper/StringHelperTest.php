<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Helper;

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

    public function pascalCaseToIdProvider(): array
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
     * @dataProvider pascalCaseToIdProvider()
     */
    public function testPascalCaseToId(string $expectedResult, string $input): void
    {
        $this->assertEquals($expectedResult, StringHelper::pascalCaseToId($input));
    }
}
