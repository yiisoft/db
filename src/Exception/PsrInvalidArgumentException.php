<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

use Yiisoft\Db\Exception\Exception;

/**
 * Represents an exception that's caused by invalid operations of cache.
 */
final class PsrInvalidArgumentException extends Exception implements \Psr\SimpleCache\InvalidArgumentException
{
}
