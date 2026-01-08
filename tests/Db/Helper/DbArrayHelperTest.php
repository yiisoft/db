<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Helper;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Tests\Provider\DbArrayHelperProvider;
use Exception;

use const E_WARNING;

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

    #[DataProviderExternal(DbArrayHelperProvider::class, 'index')]
    public function testIndex(array $rows): void
    {
        $this->assertSame($rows, DbArrayHelper::index($rows));
    }

    #[DataProviderExternal(DbArrayHelperProvider::class, 'indexWithIndexBy')]
    public function testPopulateWithIndexBy(
        array $expected,
        array $rows,
        Closure|string|null $indexBy = null,
        ?Closure $resultCallback = null,
    ): void {
        $this->assertSame($expected, DbArrayHelper::index($rows, $indexBy, $resultCallback));
    }

    #[DataProviderExternal(DbArrayHelperProvider::class, 'indexWithIndexBy')]
    public function testIndexWithIndexByWithObject(
        array $expected,
        array $rows,
        Closure|string|null $indexBy = null,
        ?Closure $resultCallback = null,
    ): void {
        $rows = json_decode(json_encode($rows));
        $populated = json_decode(json_encode(DbArrayHelper::index($rows, $indexBy, $resultCallback)), true);

        $this->assertSame($expected, $populated);
    }

    public function testArrangeWithNonExistingKey(): void
    {
        $rows = [
            ['key' => 'value1'],
            ['key' => 'value2'],
        ];

        set_error_handler(static function (int $errno, string $errstr) {
            restore_error_handler();
            throw new Exception('E_WARNING: ' . $errstr, $errno);
        }, E_WARNING);

        $this->expectExceptionMessage('E_WARNING: Undefined array key "non-existing-key"');

        DbArrayHelper::arrange($rows, ['non-existing-key']);
    }

    #[DataProviderExternal(DbArrayHelperProvider::class, 'arrange')]
    public function testArrange(
        array $expected,
        array $rows,
        array $arrangeBy = [],
        Closure|string|null $indexBy = null,
        ?Closure $resultCallback = null,
    ): void {
        $this->assertSame($expected, DbArrayHelper::arrange($rows, $arrangeBy, $indexBy, $resultCallback));
    }

    #[DataProviderExternal(DbArrayHelperProvider::class, 'toArray')]
    public function testToArray(array|object $value, array $expected): void
    {
        $this->assertSame($expected, DbArrayHelper::toArray($value));
    }
}
