<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Sqlite\Tests\QueryBuilderTest as SqliteQueryBuilderTest;

/**
 * @group sqlite
 */
final class QueryBuilderTest extends SqliteQueryBuilderTest
{
    protected ?string $driverName = 'sqlite';

    protected string $likeEscapeCharSql = " ESCAPE '\\'";
}
