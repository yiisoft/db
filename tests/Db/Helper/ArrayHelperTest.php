<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Helper;

use Closure;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Helper\ArrayHelper;

/**
 * @group db
 */
final class ArrayHelperTest extends TestCase
{
    public function testIsAssociative(): void
    {
        $this->assertFalse(ArrayHelper::isAssociative([]));
        $this->assertTrue(ArrayHelper::isAssociative(['test' => 1]));
        $this->assertFalse(ArrayHelper::isAssociative([1]));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\PopulateProvider::populate
     */
    public function testPopulate(array $rows): void
    {
        $this->assertSame($rows, ArrayHelper::populate($rows));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\PopulateProvider::populateWithIndexBy
     * @dataProvider \Yiisoft\Db\Tests\Provider\PopulateProvider::populateWithIncorrectIndexBy
     * @dataProvider \Yiisoft\Db\Tests\Provider\PopulateProvider::populateWithIndexByClosure
     */
    public function testPopulateWithIndexBy(Closure|string|null $indexBy, array $rows, array $populated): void
    {
        $this->assertSame($populated, ArrayHelper::populate($rows, $indexBy));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\PopulateProvider::populateWithIndexBy
     */
    public function testPopulateWithIndexByWithObject(Closure|string|null $indexBy, array $rows, array $expectedPopulated): void
    {
        $rows = json_decode(json_encode($rows));
        $populated = json_decode(json_encode(ArrayHelper::populate($rows, $indexBy)), true);

        $this->assertSame($expectedPopulated, $populated);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\PopulateProvider::populateWithIncorrectIndexBy
     */
    public function testPopulateWithIncorrectIndexByWithObject(Closure|string|null $indexBy, array $rows): void
    {
        $rows = json_decode(json_encode($rows));

        set_error_handler(static function (int $errno, string $errstr) {
            throw new \Exception('E_WARNING: ' . $errstr, $errno);
        }, E_WARNING);

        $this->expectExceptionMessageMatches('/^E_WARNING: /');

        ArrayHelper::populate($rows, $indexBy);

        restore_error_handler();
    }
}
