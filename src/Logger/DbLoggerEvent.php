<?php

namespace Yiisoft\Db\Logger;

class DbLoggerEvent
{
    public const CONNECTION_BEGIN = 'connection_begin';
    public const CONNECTION_END = 'connection_end';
    public const CONNECTION_ERROR = 'connection_error';

    public const TRANSACTION_BEGIN_TRANS = 'transaction_begin_trans';
    public const TRANSACTION_BEGIN_NESTED_ERROR = 'transaction_begin_nested_error';
    public const TRANSACTION_BEGIN_SAVEPOINT = 'transaction_begin_savepoint';

    public const TRANSACTION_COMMIT = 'transaction_commit';
    public const TRANSACTION_COMMIT_NESTED_ERROR = 'transaction_commit_nested_error';
    public const TRANSACTION_RELEASE_SAVEPOINT = 'transaction_release_savepoint';

    public const TRANSACTION_ROLLBACK = 'transaction_rollback';
    public const TRANSACTION_ROLLBACK_ON_LEVEL = 'tran_rollback_on_level';
    public const TRANSACTION_ROLLBACK_SAVEPOINT = 'transaction_rollback_savepoint';
    public const TRANSACTION_ROLLBACK_NESTED_ERROR = 'transaction_rollback_nested_error';

    public const TRANSACTION_SET_ISOLATION_LEVEL = 'tran_set_isolation_level';

    public const QUERY = 'query';
}
