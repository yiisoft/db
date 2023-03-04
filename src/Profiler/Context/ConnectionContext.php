<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler\Context;

final class ConnectionContext extends AbstractContext
{
    protected string $type = 'connection';
}
