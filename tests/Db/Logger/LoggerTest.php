<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Db\Logger\Context\QueryContext;
use Yiisoft\Db\Logger\DbLogger;
use Yiisoft\Db\Logger\DbLoggerEvent;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class LoggerTest extends TestCase
{
    use TestTrait;

    public function testWithDefaultLevel(): void
    {
        $queryContext = new QueryContext(__METHOD__, 'SQL', 'category');

        $logger = new DbLogger($this->createPsrLogger(LogLevel::INFO, 'SQL', ['category']));

        $logger->log(DbLoggerEvent::QUERY, $queryContext);
    }

    public function testWithOverrideLevel(): void
    {
        $queryContext = new QueryContext(__METHOD__, 'SQL', 'category');

        $logger = new DbLogger($this->createPsrLogger(LogLevel::WARNING, 'SQL', ['category']));
        $logger->setLevel(DbLoggerEvent::QUERY, LogLevel::WARNING);

        $logger->log(DbLoggerEvent::QUERY, $queryContext);
    }

    public function testWithoutLevel(): void
    {
        $queryContext = new QueryContext(__METHOD__, 'SQL', 'category');

        $logger = new DbLogger($this->createPsrLogger(LogLevel::DEBUG, 'SQL', ['category']));

        $logger->log('unknown_event', $queryContext);
    }

    private function createPsrLogger(string $level, string $sql, array $params): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $level,
                $sql,
                $params
            );

        return $logger;
    }
}
