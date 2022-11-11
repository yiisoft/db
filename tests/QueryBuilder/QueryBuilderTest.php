<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder;

use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    use SchemaBuilderTrait;
    use TestTrait;
}
