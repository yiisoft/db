<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use Stringable;

/**
 * The Exception class represents an exception that is caused by some DB-related operations. It provides additional
 * information about the error that is caused by the exception.
 */
class Exception extends \Exception implements Stringable
{
    public function __construct(string $message, public array|null $errorInfo = [], \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string readable representation of exception
     */
    public function __toString(): string
    {
        return parent::__toString() . PHP_EOL . 'Additional Information:' . PHP_EOL . print_r($this->errorInfo, true);
    }
}
