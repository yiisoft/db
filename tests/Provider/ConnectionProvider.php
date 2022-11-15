<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

final class ConnectionProvider
{
    public function execute(): array
    {
        $baseConnectionProvider = new BaseConnectionProvider();

        return $baseConnectionProvider->execute();
    }
}
