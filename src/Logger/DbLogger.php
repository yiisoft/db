<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Logger\Context\ConnectionContext;
use Yiisoft\Db\Logger\Context\QueryContext;
use Yiisoft\Db\Logger\Context\TransactionContext;

class DbLogger implements DbLoggerInterface
{

    private const DEFAULT_LOG_LEVEL = LogLevel::DEBUG;

    private static array $LOG_LEVELS = [
        DbLoggerEvent::CONNECTION_BEGIN => LogLevel::INFO,
        DbLoggerEvent::CONNECTION_END => LogLevel::DEBUG,
        DbLoggerEvent::CONNECTION_ERROR => LogLevel::ERROR,

        DbLoggerEvent::TRANSACTION_BEGIN_TRANS => LogLevel::DEBUG,
        DbLoggerEvent::TRANSACTION_BEGIN_SAVEPOINT => LogLevel::DEBUG,
        DbLoggerEvent::TRANSACTION_BEGIN_NESTED_ERROR => LogLevel::DEBUG,

        DbLoggerEvent::TRANSACTION_COMMIT => LogLevel::DEBUG,
        DbLoggerEvent::TRANSACTION_RELEASE_SAVEPOINT => LogLevel::DEBUG,
        DbLoggerEvent::TRANSACTION_COMMIT_NESTED_ERROR => LogLevel::INFO,

        DbLoggerEvent::TRANSACTION_ROLLBACK => LogLevel::INFO,
        DbLoggerEvent::TRANSACTION_ROLLBACK_SAVEPOINT => LogLevel::DEBUG,
        DbLoggerEvent::TRANSACTION_ROLLBACK_NESTED_ERROR => LogLevel::INFO,

        DbLoggerEvent::TRANSACTION_SET_ISOLATION_LEVEL => LogLevel::DEBUG,
        DbLoggerEvent::TRANSACTION_ROLLBACK_ON_LEVEL => LogLevel::ERROR,

        DbLoggerEvent::QUERY => LogLevel::INFO,
    ];

    public function __construct(protected PsrLoggerInterface $logger)
    {
    }

    public function log(string $logEvent, ContextInterface $context): void
    {
        if ($context instanceof ConnectionContext) {
            $this->logConnection($logEvent, $context->getMethodName(), $context->getDsn());
        }

        if ($context instanceof TransactionContext) {
            $this->logTransaction($logEvent, $context->getIsolationLevel(), $context->getLevel(), $context->getMethodName(), $context->getException());
        }

        if ($context instanceof QueryContext) {
            $this->logger->log(self::$LOG_LEVELS[$logEvent] ?? self::DEFAULT_LOG_LEVEL, $context->getRawSql(), [$context->getCategory()]);
        }
    }

    public function setLevel(string $logEvent, string $level): void
    {
        self::$LOG_LEVELS[$logEvent] = $level;
    }

    private function logConnection(string $logEvent, string $methodName, string $dsn): void
    {
        $logLevel = self::$LOG_LEVELS[$logEvent] ?? self::DEFAULT_LOG_LEVEL;
        $message = match ($logEvent) {
            DbLoggerEvent::CONNECTION_BEGIN, DbLoggerEvent::CONNECTION_ERROR =>
                'Opening DB connection: ' . $dsn,
            DbLoggerEvent::CONNECTION_END => 'Closing DB connection: ' . $dsn . ' ' . $methodName,
        };
        $this->logger->log($logLevel, $message);
    }

    private function logTransaction(string $logType, string|null $isolationLevel, int $level, string $methodName, ?Throwable $exception = null): void
    {
        $logLevel = self::$LOG_LEVELS[$logType] ?? self::DEFAULT_LOG_LEVEL;

        if ($logType === DbLoggerEvent::TRANSACTION_ROLLBACK_ON_LEVEL) {
            $this->logger->log($logLevel, (string)$exception, [$methodName]);
            return;
        }

        $message = match ($logType) {
            DbLoggerEvent::TRANSACTION_BEGIN_TRANS => 'Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : '') . ' ' . $methodName,
            DbLoggerEvent::TRANSACTION_BEGIN_SAVEPOINT => 'Set savepoint ' . $level . ' ' . $methodName,
            DbLoggerEvent::TRANSACTION_BEGIN_NESTED_ERROR => 'Transaction not started: nested transaction not supported ' . $methodName,

            DbLoggerEvent::TRANSACTION_COMMIT => 'Commit transaction ' . $methodName,
            DbLoggerEvent::TRANSACTION_RELEASE_SAVEPOINT => 'Release savepoint ' . $level . ' ' . $methodName,
            DbLoggerEvent::TRANSACTION_COMMIT_NESTED_ERROR => 'Transaction not committed: nested transaction not supported ' . $methodName,

            DbLoggerEvent::TRANSACTION_ROLLBACK => 'Roll back transaction ' . $methodName,
            DbLoggerEvent::TRANSACTION_ROLLBACK_SAVEPOINT => 'Roll back to savepoint ' . $level . ' ' . $methodName,
            DbLoggerEvent::TRANSACTION_ROLLBACK_NESTED_ERROR => 'Transaction not rolled back: nested transaction not supported ' . $methodName,

            DbLoggerEvent::TRANSACTION_SET_ISOLATION_LEVEL => 'Setting transaction isolation level to ' . $level . ' ' . $methodName,
        };
        $this->logger->log($logLevel, $message);
    }
}
