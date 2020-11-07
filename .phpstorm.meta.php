<?php
// .phpstorm.meta.php

namespace PHPSTORM_META {

    expectedArguments(
        \Yiisoft\Db\Transaction\Transaction::begin(),
        0,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_READ_UNCOMMITTED,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_READ_COMMITTED,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_REPEATABLE_READ,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_SERIALIZABLE
    );

    expectedArguments(
        \Yiisoft\Db\Transaction\Transaction::setIsolationLevel(),
        0,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_READ_UNCOMMITTED,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_READ_COMMITTED,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_REPEATABLE_READ,
        \Yiisoft\Db\Transaction\Transaction::LEVEL_SERIALIZABLE
    );
}
