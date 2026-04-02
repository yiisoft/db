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
    private const MSG_INTEGRITY_EXCEPTION_1 = 'SQLSTATE[23';
    private const MGS_INTEGRITY_EXCEPTION_2 = 'ORA-00001: unique constraint';

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

        $isIntegrity = str_contains($message, self::MSG_INTEGRITY_EXCEPTION_1)
            || str_contains($message, self::MGS_INTEGRITY_EXCEPTION_2)
            || $this->isIntegrityConstraintViolation($errorInfo);

        return match ($isIntegrity) {
            true => new IntegrityException($message, $errorInfo, $this->e),
            default => new Exception($message, $errorInfo, $this->e),
        };
    }

    /**
     * Checks if the error is an integrity constraint violation using SQLSTATE code.
     *
     * SQLSTATE class 23 covers all integrity constraint violations across DBMS.
     * The previous SQLSTATE[HY match was too broad (HY = CLI-specific condition class)
     * and could cause false positives.
     *
     * @param array|null $errorInfo PDO error info array [SQLSTATE, driver code, message]
     */
    private function isIntegrityConstraintViolation(?array $errorInfo): bool
    {
        if ($errorInfo === null || !isset($errorInfo[0])) {
            return false;
        }

        return str_starts_with((string) $errorInfo[0], '23');
    }
}
