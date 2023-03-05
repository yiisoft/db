<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler\Context;

final class CommandContext extends AbstractContext
{
    private const LOG_CONTEXT = 'logContext';
    private const SQL = 'sql';
    private const PARAMS = 'params';

    public function __construct(
        private string $method,
        private string $logContext,
        private string $sql,
        private array $params,
    ) {
        parent::__construct($this->method);
    }

    public function getType(): string
    {
        return 'command';
    }

    public function asArray(): array
    {
        return parent::asArray() + [
            self::LOG_CONTEXT => $this->logContext,
            self::SQL => $this->sql,
            self::PARAMS => $this->params,
        ];
    }
}
