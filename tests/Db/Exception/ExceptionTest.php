<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Exception;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new Exception('test');

        $this->assertSame('test', $exception->getMessage());
    }

    public function testExceptionStringable(): void
    {
        $exception = new Exception('test');

        $this->assertStringContainsString(
            'Yiisoft\Db\Exception\Exception: test in',
            (string) $exception,
        );
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('previous');
        $exception = new Exception('test', [], $previous);

        $this->assertSame('test', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
