<?php
declare(strict_types=1);

namespace Yiisoft\Db\Exception;

/**
 * Exception represents an exception that is caused by violation of DB constraints.
 */
class IntegrityException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Integrity constraint violation';
    }
}
