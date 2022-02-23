<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use PDOException;

final class ConvertException
{
    private const MSG_INTEGRITY_EXCEPTION_1 = 'SQLSTATE[23';
    private const MGS_INTEGRITY_EXCEPTION_2 = 'ORA-00001: unique constraint';

    public function __construct(private \Exception $e, private string $rawSql)
    {
    }

    public function run(): Exception
    {
        if ($this->e instanceof Exception) {
            return $this->e;
        }

        $message = $this->e->getMessage() . PHP_EOL . 'The SQL being executed was: ' . $this->rawSql;
        $errorInfo = $this->e instanceof PDOException ? $this->e->errorInfo : null;
        $exception = new Exception($message, $errorInfo, $this->e);

        if (
            str_contains($message, self::MSG_INTEGRITY_EXCEPTION_1)
            || str_contains($message, self::MGS_INTEGRITY_EXCEPTION_2)
        ) {
            $exception = new IntegrityException($message, $errorInfo, $this->e);
        }

        return $exception;
    }
}
