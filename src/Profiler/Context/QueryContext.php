<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler\Context;

final class QueryContext extends AbstractContext
{
    private const LOG_CONTEXT = 'logContext';
    private const SQL = 'sql';
    private const PARAMS = 'params';

    protected string $type = 'query';

    public function __construct(
        private string $method,
        private string $logContext,
        private string $sql,
        private array $params,
    ) {
        parent::__construct($this->method);
    }

    public function __toArray(): array
    {
        return parent::__toArray() + [
            self::LOG_CONTEXT => $this->logContext,
            self::SQL => $this->sql,
            self::PARAMS => $this->params,
        ];
    }
}
