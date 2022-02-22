<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use PDOException;

final class ConvertException
{
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

        if (str_contains($message, 'SQLSTATE[23') || str_contains($message, 'ORA-00001: unique constraint')) {
            $exception = new IntegrityException($message, $errorInfo, $this->e);
        }

        return $exception;
    }
}
