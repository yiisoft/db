<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Helper;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Tests\Provider\DbArrayHelperProvider;

/**
 * @group db
 */
final class DbArrayHelperTest extends TestCase
{
    public function testIsAssociative(): void
    {
        $this->assertFalse(DbArrayHelper::isAssociative([]));
        $this->assertTrue(DbArrayHelper::isAssociative(['test' => 1]));
        $this->assertFalse(DbArrayHelper::isAssociative([1]));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\DbArrayHelperProvider::index
     */
    public function testIndex(array $rows): void
    {
        $this->assertSame($rows, DbArrayHelper::index($rows));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\DbArrayHelperProvider::indexWithIndexBy
     * @dataProvider \Yiisoft\Db\Tests\Provider\DbArrayHelperProvider::indexWithIncorrectIndexBy
     * @dataProvider \Yiisoft\Db\Tests\Provider\DbArrayHelperProvider::indexWithIndexByClosure
     */
    public function testPopulateWithIndexBy(Closure|string|null $indexBy, array $rows, array $expected): void
    {
        $this->assertSame($expected, DbArrayHelper::index($rows, $indexBy));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\DbArrayHelperProvider::indexWithIndexBy
     */
    public function testIndexWithIndexByWithObject(Closure|string|null $indexBy, array $rows, array $expected): void
    {
        $rows = json_decode(json_encode($rows));
        $populated = json_decode(json_encode(DbArrayHelper::index($rows, $indexBy)), true);

        $this->assertSame($expected, $populated);
    }

    public function testIndexWithNonExistingIndexBy(): void
    {
        $rows = [
            ['key' => 'value1'],
            ['key' => 'value2'],
        ];

        $this->assertSame($rows, DbArrayHelper::index($rows, 'non-existing-key'));

        set_error_handler(static function (int $errno, string $errstr) {
            restore_error_handler();
            throw new \Exception('E_WARNING: ' . $errstr, $errno);
        }, E_WARNING);

        $this->expectExceptionMessage('E_WARNING: Undefined array key "non-existing-key"');

        DbArrayHelper::index($rows, 'non-existing-key', ['key']);
    }

    public function testIndexWithArrangeBy(): void
    {
        $rows = [
            ['key' => 'value1'],
            ['key' => 'value2'],
        ];

        set_error_handler(static function (int $errno, string $errstr) {
            restore_error_handler();
            throw new \Exception('E_WARNING: ' . $errstr, $errno);
        }, E_WARNING);

        $this->expectExceptionMessage('E_WARNING: Undefined array key "non-existing-key"');

        DbArrayHelper::index($rows, null, ['non-existing-key']);
    }

    public function testIndexWithClosureIndexByAndArrangeBy(): void
    {
        $rows = [
            ['key' => 'value1'],
            ['key' => 'value2'],
        ];

        $this->assertSame([
            'value1' => [
                'value1' => ['key' => 'value1'],
            ],
            'value2' => [
                'value2' => ['key' => 'value2'],
            ],
        ], DbArrayHelper::index($rows, fn ($row) => $row['key'], ['key']));
    }

    #[DataProviderExternal(DbArrayHelperProvider::class, 'toArray')]
    public function testToArray(array|object $value, array $expected): void
    {
        $this->assertSame($expected, DbArrayHelper::toArray($value));
    }
}
