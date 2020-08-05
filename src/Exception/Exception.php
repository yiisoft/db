<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

/**
 * Exceptions represents an exception that is caused by some DB-related operations.
 */
class Exception extends \Exception
{
    /**
     * @var array the error info provided by a PDO exception. This is the same as returned
     * by [PDO::errorInfo](http://www.php.net/manual/en/pdo.errorinfo.php).
     */
    public ?array $errorInfo;

    /**
     * Constructor.
     *
     * @param string $message PDO error message
     * @param array|null $errorInfo PDO error info
     * @param string $code PDO error code
     * @param \Exception $previous  The previous exception used for the exception chaining.
     */
    public function __construct(string $message, ?array $errorInfo = [], string $code = '', \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorInfo = $errorInfo;
        $this->code = $code;
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
