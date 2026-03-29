<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Exception;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\ConnectionException;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Exception\IntegrityException;

use const PHP_EOL;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConvertExceptionTest extends TestCase
{
    #[DataProvider('integrityExceptionMessages')]
    public function testIntegrityException(string $message): void
    {
        $exception = (new ConvertException(new Exception($message), 'INSERT INTO test'))->run();

        $this->assertInstanceOf(IntegrityException::class, $exception);
    }

    public function testRun(): void
    {
        $e = new Exception('test');
        $rawSql = 'SELECT * FROM test';
        $convertException = new ConvertException($e, $rawSql);
        $exception = $convertException->run();

        $this->assertSame($e, $exception->getPrevious());
        $this->assertSame('test' . PHP_EOL . 'The SQL being executed was: ' . $rawSql, $exception->getMessage());
    }

    #[DataProvider('connectionExceptionMessages')]
    public function testConnectionException(string $message): void
    {
        $exception = (new ConvertException(new Exception($message), 'SELECT 1'))->run();

        $this->assertInstanceOf(ConnectionException::class, $exception);
    }

    #[DataProvider('generalExceptionMessages')]
    public function testGeneralException(string $message): void
    {
        $exception = (new ConvertException(new Exception($message), 'SELECT 1'))->run();

        $this->assertNotInstanceOf(IntegrityException::class, $exception);
        $this->assertNotInstanceOf(ConnectionException::class, $exception);
    }

    public static function connectionExceptionMessages(): array
    {
        return [
            'connection exception' => ['SQLSTATE[08000]: Connection exception'],
            'sqlclient unable to establish connection' => ['SQLSTATE[08001]: SQL-client unable to establish SQL-connection'],
            'connection does not exist' => ['SQLSTATE[08003]: Connection does not exist'],
            'sqlserver rejected connection' => ['SQLSTATE[08004]: SQL server rejected establishment of SQL-connection'],
            'connection failure' => ['SQLSTATE[08006]: Connection failure: 7 no connection to the server'],
        ];
    }

    public static function generalExceptionMessages(): array
    {
        return [
            'general error' => ['SQLSTATE[HY000]: General error: 7 no connection to the server'],
            'oracle table does not exist' => ['ORA-00942: table or view does not exist'],
        ];
    }

    public static function integrityExceptionMessages(): array
    {
        return [
            'sqlstate class 23' => ['SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed'],
            'oracle unique constraint' => ['ORA-00001: unique constraint (SYS.PK_ID) violated'],
            'oracle cannot insert null' => ['ORA-01400: cannot insert NULL into ("SYS"."PROFILE"."DESCRIPTION")'],
            'oracle cannot update null' => ['ORA-01407: cannot update ("SYS"."PROFILE"."DESCRIPTION") to NULL'],
            'oracle check constraint' => ['ORA-02290: check constraint (SYS.CK_PROFILE_DESCRIPTION) violated'],
            'oracle parent key not found' => ['ORA-02291: integrity constraint (SYS.FK_PROFILE_CUSTOMER) violated - parent key not found'],
            'oracle child record found' => ['ORA-02292: integrity constraint (SYS.FK_PROFILE_CUSTOMER) violated - child record found'],
        ];
    }
}
