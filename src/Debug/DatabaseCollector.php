<?php

declare(strict_types=1);

namespace Yiisoft\Db\Debug;

use Throwable;
use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;

final class DatabaseCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private const ACTION_QUERY_START = 'query.start';
    private const ACTION_QUERY_END = 'query.end';
    private const ACTION_QUERY_ERROR = 'query.error';

    private const ACTION_TRANSACTION_START = 'transaction.start';
    private const ACTION_TRANSACTION_ROLLBACK = 'transaction.rollback';
    private const ACTION_TRANSACTION_COMMIT = 'transaction.commit';

    private const TRANSACTION_STATUS_COMMIT = 'commit';
    private const TRANSACTION_STATUS_ROLLBACK = 'rollback';
    private const TRANSACTION_STATUS_START = 'start';

    private const QUERY_STATUS_INITIALIZED = 'initialized';
    private const QUERY_STATUS_ERROR = 'error';
    private const QUERY_STATUS_SUCCESS = 'success';

    private array $queries = [];
    private array $transactions = [];

    private int $position = 0;
    private int $currentTransactionId = 0;

    public function collectQueryStart(
        string $id,
        string $sql,
        string $rawSql,
        array $params,
        string $line,
    ): void {
        $this->queries[$id] = [
            'position' => $this->position++,
            'transactionId' => $this->currentTransactionId,
            'sql' => $sql,
            'rawSql' => $rawSql,
            'params' => $params,
            'line' => $line,
            'status' => self::QUERY_STATUS_INITIALIZED,
            'actions' => [
                'action' => self::ACTION_QUERY_START,
                'time' => microtime(true),
            ],
        ];
    }

    public function collectQueryEnd(
        string $id,
        int $rowsNumber,
    ): void {
        $this->queries[$id]['rowsNumber'] = $rowsNumber;
        $this->queries[$id]['status'] = self::QUERY_STATUS_SUCCESS;
        $this->queries[$id]['actions'][] = [
            'action' => self::ACTION_QUERY_END,
            'time' => microtime(true),
        ];
    }

    public function collectQueryError(
        string $id,
        Throwable $exception,
    ): void {
        $this->queries[$id]['exception'] = $exception;
        $this->queries[$id]['status'] = self::QUERY_STATUS_ERROR;
        $this->queries[$id]['actions'][] = [
            'action' => self::ACTION_QUERY_ERROR,
            'time' => microtime(true),
        ];
    }

    public function collectTransactionStart(
        ?string $isolationLevel,
        string $line,
    ): void {
        $id = ++$this->currentTransactionId;
        $this->transactions[$id] = [
            'id' => $id,
            'position' => $this->position++,
            'status' => self::TRANSACTION_STATUS_START,
            'line' => $line,
            'level' => $isolationLevel,
            'actions' => [
                'action' => self::ACTION_TRANSACTION_START,
                'time' => microtime(true),
            ],
        ];
    }

    public function collectTransactionRollback(
        string $line,
    ) {
        $this->transactions[$this->currentTransactionId]['status'] = self::TRANSACTION_STATUS_ROLLBACK;
        $this->transactions[$this->currentTransactionId]['actions'] = [
            'action' => self::ACTION_TRANSACTION_ROLLBACK,
            'line' => $line,
            'time' => microtime(true),
        ];
        ++$this->currentTransactionId;
    }

    public function collectTransactionCommit(
        string $line,
    ) {
        $this->transactions[$this->currentTransactionId]['status'] = self::TRANSACTION_STATUS_COMMIT;
        $this->transactions[$this->currentTransactionId]['actions'] = [
            'action' => self::ACTION_TRANSACTION_COMMIT,
            'line' => $line,
            'time' => microtime(true),
        ];
        ++$this->currentTransactionId;
    }

    public function getCollected(): array
    {
        usort($this->queries, fn (array $a, array $b) => $a['position'] <=> $b['position']);

        return [
            'queries' => array_values($this->queries),
            'transactions' => array_values($this->transactions),
        ];
    }

    public function getSummary(): array
    {
        return [
            'db' => [
                'queries' => [
                    'error' => count(
                        array_filter($this->queries, fn (array $query) => $query['status'] === self::QUERY_STATUS_ERROR)
                    ),
                    'total' => count($this->queries),
                ],
                'transactions' => [
                    'error' => count(
                        array_filter(
                            $this->transactions,
                            fn (array $query) => $query['status'] === self::TRANSACTION_STATUS_ROLLBACK
                        )
                    ),
                    'total' => count($this->transactions),
                ],
            ],
        ];
    }

    private function reset(): void
    {
        $this->queries = [];
    }
}
