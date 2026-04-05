<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use PDOException;

use const PHP_EOL;

/**
 * Converts an exception into a more specific one.
 *
 * For example, if an exception is caused by a violation of a unique key constraint, it will be converted into an
 * {@see IntegrityException} exception. If caused by a lost or refused connection, it will be converted into a
 * {@see ConnectionException}.
 */
final class ConvertException
{
    private const MSG_INTEGRITY_EXCEPTION_1 = 'SQLSTATE[23';
    private const MGS_INTEGRITY_EXCEPTION_2 = 'ORA-00001: unique constraint';
    private const MSG_INTEGRITY_EXCEPTION_3 = 'SQLSTATE[HY';

    private const MSG_CONNECTION_EXCEPTIONS = [
        'no connection',
        'General error: 7',
        'gone away',
        'Connection refused',
        'Lost connection',
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

        foreach (self::MSG_CONNECTION_EXCEPTIONS as $pattern) {
            if (str_contains($message, $pattern)) {
                return new ConnectionException($message, $errorInfo, $this->e);
            }
        }

        return match (
            str_contains($message, self::MSG_INTEGRITY_EXCEPTION_1)
            || str_contains($message, self::MGS_INTEGRITY_EXCEPTION_2)
            || str_contains($message, self::MSG_INTEGRITY_EXCEPTION_3)
        ) {
            true => new IntegrityException($message, $errorInfo, $this->e),
            default => new Exception($message, $errorInfo, $this->e),
        };
    }
}
