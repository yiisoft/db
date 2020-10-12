<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

/**
 * Exceptions represents an exception that is caused by some DB-related operations.
 */
class Exception extends \Exception
{
    /**
     * @var array|null the error info provided by a PDO exception. This is the same as returned by
     * [PDO::errorInfo](http://www.php.net/manual/en/pdo.errorinfo.php).
     */
    public ?array $errorInfo;

    public function __construct(string $message, ?array $errorInfo = [], \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorInfo = $errorInfo;
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
