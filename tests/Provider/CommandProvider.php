<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\TestTrait;

final class CommandProvider
{
    use TestTrait;

    public function bindParamsNonWhere(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->bindParamsNonWhere();
    }

    public function invalidSelectColumns(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->invalidSelectColumns();
    }

    public function rawSql(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->rawSql();
    }

    public function upsert(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->upsert($this->getConnection());
    }

}
