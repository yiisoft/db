<?php
declare(strict_types=1);

namespace Yiisoft\Db\Exception;

/**
 * UnknownPropertyException represents an exception caused by accessing unknown object properties.
 */
class UnknownPropertyException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Unknown Property';
    }
}
