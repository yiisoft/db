<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Syntax;

use Yiisoft\Db\Syntax\AbstractSqlParser;
use Yiisoft\Db\Tests\Common\CommonSqlParserTest;
use Yiisoft\Db\Tests\Support\Stub\SqlParser;

final class SqlParserTest extends CommonSqlParserTest
{
    protected function createSqlParser(string $sql): AbstractSqlParser
    {
        return new SqlParser($sql);
    }
}
