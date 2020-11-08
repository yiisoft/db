<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

interface DSNInterface
{
    public function __toString();

    public function getClass(): string;
}
