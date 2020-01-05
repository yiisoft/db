<?php
declare(strict_types=1);

namespace Yiisoft\Db\Exception;

/**
 * Class InvalidConfigException.
 */
class InvalidConfigException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Configuration';
    }
}
