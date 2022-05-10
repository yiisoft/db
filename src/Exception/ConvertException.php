<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use PDOException;

final class ConvertException
{
    private const MSG_INTEGRITY_EXCEPTION_1 = 'SQLSTATE[23';
    private const MGS_INTEGRITY_EXCEPTION_2 = 'ORA-00001: unique constraint';
    private const MSG_INTEGRITY_EXCEPTION_3 = 'SQLSTATE[HY';

    public function __construct(private \Exception $e, private string $rawSql)
    {
    }

    public function run(): Exception
    {
        $message = $this->e->getMessage() . PHP_EOL . 'The SQL being executed was: ' . $this->rawSql;
        /** @var array|null */
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
