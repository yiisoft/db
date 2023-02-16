<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\ConvertException;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConvertExceptionTest extends TestCase
{
    public function testRun(): void
    {
        $e = new Exception('test');
        $rawSql = 'SELECT * FROM test';
        $convertException = new ConvertException($e, $rawSql);
        $exception = $convertException->run();

        $this->assertSame($e, $exception->getPrevious());
        $this->assertSame('test' . PHP_EOL . 'The SQL being executed was: ' . $rawSql, $exception->getMessage());
    }
}
