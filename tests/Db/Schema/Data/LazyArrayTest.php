<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Data;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Data\LazyArray;

/**
 * @group db
 */
final class LazyArrayTest extends TestCase
{
    public function testNullValue()
    {
        $lazyArray = new LazyArray('null');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array value must be a valid string representation.');

        $lazyArray->getValue();
    }
}
