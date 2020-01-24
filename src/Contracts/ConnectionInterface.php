<?php

namespace Yiisoft\Db\Contracts;

interface ConnectionInterface
{
    public function open();

    public function close();
}
