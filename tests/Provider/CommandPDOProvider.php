<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

final class CommandPDOProvider
{
    public function bindParam(): array
    {
        $baseCommandPDOProvider = new BaseCommandPDOProvider();

        return $baseCommandPDOProvider->bindParam();
    }

    public function bindParamsNonWhere(): array
    {
        $baseCommandPDOProvider = new BaseCommandPDOProvider();

        return $baseCommandPDOProvider->bindParamsNonWhere();
    }
}
