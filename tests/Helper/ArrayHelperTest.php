<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Helper\ArrayHelper;

/**
 * @group db
 */
final class ArrayHelperTest extends TestCase
{
    public function testIsAssociative(): void
    {
        $this->assertTrue(ArrayHelper::isAssociative(['test' => 1]));
        $this->assertFalse(ArrayHelper::isAssociative([1]));
    }
}
