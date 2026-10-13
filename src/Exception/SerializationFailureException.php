<?php

declare(strict_types=1);

namespace Yiisoft\Db\Exception;

/**
 * Represents an exception caused by a serialization failure, for example, when a transaction fails to
 * serialize concurrent operations (SQLSTATE code 40001).
 */
final class SerializationFailureException extends Exception {}
