<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Data;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;

/**
 * @group db
 */
final class LazyArrayStructuredTest extends TestCase
{
    public function testNullValue()
    {
        $lazyArray = new StructuredLazyArray('null');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Structured value must be a valid string representation.');

        $lazyArray->getValue();
    }
}
