<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;

final class PDOProvider
{
    public function attributes(): array
    {
        return [
            [[PDO::ATTR_EMULATE_PREPARES => true]],
            [[PDO::ATTR_EMULATE_PREPARES => false]],
        ];
    }
}
