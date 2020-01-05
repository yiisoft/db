<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Db\Query;

class UnqueryableQueryMock extends Query
{
    /**
     * {@inheritdoc}
     */
    public function one($db = null)
    {
        throw new \InvalidCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function all($db = null)
    {
        throw new \InvalidCallException();
    }
}
