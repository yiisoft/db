<?php

declare(strict_types=1);

namespace Yiisoft\Db\AwareTrait;

use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }

    protected function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
