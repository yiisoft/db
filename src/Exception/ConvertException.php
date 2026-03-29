<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use PDOException;

use const PHP_EOL;

/**
 * Converts an exception into a more specific one.
 *
 * For example, if an exception is caused by a violation of a unique key constraint, it will be converted into an
 * {@see IntegrityException} exception.
 */
final class ConvertException
{
    private const MSG_CONNECTION_EXCEPTION = 'SQLSTATE[08';
    private const MSG_INTEGRITY_EXCEPTION = 'SQLSTATE[23';
    private const MYSQL_RECONNECT_EXCEPTIONS = [
        'SQLSTATE[HY000]: General error: 2006 ',
        'SQLSTATE[HY000]: General error: 4031 ',
    ];
    private const ORACLE_INTEGRITY_EXCEPTIONS = [
        'ORA-00001:',
        'ORA-01400:',
        'ORA-01407:',
        'ORA-02290:',
        'ORA-02291:',
        'ORA-02292:',
    ];

    public function __construct(
        private readonly \Exception $e,
        private readonly string $rawSql,
    ) {}

    /**
     * Converts an exception into a more specific one.
     *
     * @return Exception The converted exception if it could be converted, otherwise the original exception.
     */
    public function run(): Exception
    {
        $message = $this->e->getMessage() . PHP_EOL . 'The SQL being executed was: ' . $this->rawSql;

        $errorInfo = $this->e instanceof PDOException ? $this->e->errorInfo : null;

        if (
            str_contains($message, self::MSG_INTEGRITY_EXCEPTION)
            || $this->isMysqlReconnectException($message)
            || $this->isOracleIntegrityException($message)
        ) {
            return new IntegrityException($message, $errorInfo, $this->e);
        }

        if (str_contains($message, self::MSG_CONNECTION_EXCEPTION)) {
            return new ConnectionException($message, $errorInfo, $this->e);
        }

        return new Exception($message, $errorInfo, $this->e);
    }

    private function isMysqlReconnectException(string $message): bool
    {
        foreach (self::MYSQL_RECONNECT_EXCEPTIONS as $mysqlReconnectException) {
            if (str_contains($message, $mysqlReconnectException)) {
                return true;
            }
        }

        return false;
    }

    private function isOracleIntegrityException(string $message): bool
    {
        foreach (self::ORACLE_INTEGRITY_EXCEPTIONS as $oracleIntegrityException) {
            if (str_contains($message, $oracleIntegrityException)) {
                return true;
            }
        }

        return false;
    }
}
