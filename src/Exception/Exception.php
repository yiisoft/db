<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

/**
 * Exceptions represents an exception that is caused by some DB-related operations.
 */
class Exception extends \Exception
{
    public function __construct(string $message, public ?array $errorInfo = [], \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string readable representation of exception
     */
    public function __toString(): string
    {
        return parent::__toString() . PHP_EOL .
            'Additional Information:' . PHP_EOL . print_r($this->errorInfo, true);
    }
}
