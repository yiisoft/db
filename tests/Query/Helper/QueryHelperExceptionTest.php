<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Query\Helper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Tests\Support\Mock;

final class QueryHelperExceptionTest extends TestCase
{
    public function testCleanUpTableNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('To use Expression in from() method, pass it in array format with alias.');
        Mock::queryHelper()->cleanUpTableNames([new Expression('(SELECT id FROM user)')], new Quoter('"', '"'));
    }
}
