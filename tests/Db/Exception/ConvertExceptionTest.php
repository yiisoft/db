<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Exception;

use Exception;
use PDOException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Exception\SerializationFailureException;

use const PHP_EOL;

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

    public function testRunSerializationFailure(): void
    {
        $e = new PDOException('SQLSTATE[40001]: could not serialize access due to concurrent update');
        $e->errorInfo = ['40001', 1213, 'could not serialize access due to concurrent update'];
        $rawSql = "UPDATE test SET name = 'test' WHERE id = 1";
        $convertException = new ConvertException($e, $rawSql);
        $exception = $convertException->run();

        $this->assertInstanceOf(SerializationFailureException::class, $exception);
        $this->assertSame($e, $exception->getPrevious());
    }

    public function testRunSerializationFailureMarkerInSqlOnly(): void
    {
        $e = new PDOException('General error: 2006 MySQL server has gone away');
        $rawSql = "SELECT * FROM logs WHERE message LIKE '%SQLSTATE[40001]%'";
        $convertException = new ConvertException($e, $rawSql);
        $exception = $convertException->run();

        $this->assertNotInstanceOf(SerializationFailureException::class, $exception);
        $this->assertSame($e, $exception->getPrevious());
    }
}
