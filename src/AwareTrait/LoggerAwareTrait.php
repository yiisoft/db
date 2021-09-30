<?php

declare(strict_types=1);

namespace Yiisoft\Db\AwareTrait;

use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    protected ?LoggerInterface $logger = null;

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
