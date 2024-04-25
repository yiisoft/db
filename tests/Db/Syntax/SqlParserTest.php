<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Syntax;

use Yiisoft\Db\Tests\AbstractSqlParserTest;
use Yiisoft\Db\Tests\Support\Stub\SqlParser;

class SqlParserTest extends AbstractSqlParserTest
{
    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
