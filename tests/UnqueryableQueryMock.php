<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Db\Query\Query;

class UnqueryableQueryMock extends Query
{
    public function one($db = null)
    {
        throw new \InvalidCallException();
    }

    public function all($db = null)
    {
        throw new \InvalidCallException();
    }
}
