<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use PDOException;

/**
 * Converts an exception into a more specific one.
 *
 * For example, if an exception is caused by a violation of a unique key constraint, it will be converted into an
 * {@see IntegrityException} exception.
 */
final class ConvertException
{
    private const MSG_INTEGRITY_EXCEPTION_1 = 'SQLSTATE[23';
    private const MGS_INTEGRITY_EXCEPTION_2 = 'ORA-00001: unique constraint';
    private const MSG_INTEGRITY_EXCEPTION_3 = 'SQLSTATE[HY';

    public function __construct(private \Exception $e, private string $rawSql)
    {
    }

    /**
     * Converts an exception into a more specific one.
     *
     * @return Exception The converted exception if it could be converted, otherwise the original exception.
     */
    public function run(): Exception
    {
        $message = $this->e->getMessage() . PHP_EOL . 'The SQL being executed was: ' . $this->rawSql;

        /** @var array|null $errorInfo */
        $errorInfo = $this->e instanceof PDOException ? $this->e->errorInfo : null;

        return match (
            str_contains($message, self::MSG_INTEGRITY_EXCEPTION_1) ||
            str_contains($message, self::MGS_INTEGRITY_EXCEPTION_2) ||
            str_contains($message, self::MSG_INTEGRITY_EXCEPTION_3)
        ) {
            true => new IntegrityException($message, $errorInfo, $this->e),
            default => new Exception($message, $errorInfo, $this->e),
        };
    }
}
