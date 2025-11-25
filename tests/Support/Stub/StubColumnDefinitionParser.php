<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Syntax\AbstractColumnDefinitionParser;

final class StubColumnDefinitionParser extends AbstractColumnDefinitionParser
{
    protected function parseTypeParams(string $type, string $params): array
    {
        return [];
    }
}
