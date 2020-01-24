<?php

declare(strict_types=1);

namespace Yiisoft\Db;

use Psr\Log\LoggerInterface;
use Yiisoft\Db\Contracts\ConnectionInterface;

class Database
{
    private static ConnectionInterface $db;

    private static LoggerInterface $logger;

    public function __construct(array $config = [])
    {
        static::configure($this, $config);
    }

    /**
     * Configures an object with the given configuration.
     *
     * @param object $object the object to be configured
     * @param iterable $config property values and methods to call
     *
     * @return object the object itself
     */
    public static function configure($object, iterable $config)
    {
        foreach ($config as $action => $arguments) {
            if (substr($action, -2) === '()') {
                // method call
                \call_user_func_array([$object, substr($action, 0, -2)], $arguments);
            } else {
                // property
                $object->$action = $arguments;
            }
        }

        return $object;
    }

    public static function setDb(ConnectionInterface $value): void
    {
        static::$db = $value;
    }

    public static function getDb(): ConnectionInterface
    {
        return static::$db;
    }
}
