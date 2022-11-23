<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\TestTrait;

final class CommandProvider
{
    use TestTrait;

    public function createIndex(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->createIndex($this->getConnection());
    }

    public function rawSql(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->rawSql();
    }

    public function update(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->update($this->getConnection());
    }

    public function upsert(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->upsert($this->getConnection());
    }
}
