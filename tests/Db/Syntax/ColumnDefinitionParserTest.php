<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Syntax;

use Yiisoft\Db\Syntax\ColumnDefinitionParser;
use Yiisoft\Db\Tests\Common\CommonColumnDefinitionParserTest;

/**
 * @group db
 */
final class ColumnDefinitionParserTest extends CommonColumnDefinitionParserTest
{
    protected function createColumnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }
}
