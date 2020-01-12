<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;

return [
    Aliases::class => [
        '@root' => dirname(__DIR__, 1),
        '@runtime' => '@root/tests/data/runtime',
    ],

    \Yiisoft\Cache\CacheInterface::class => static function (ContainerInterface $container) {
        return new Cache(new ArrayCache());
    },

    FileRotatorInterface::class => [
        '__class' => FileRotator::class,
        '__construct()' => [
            10
        ]
    ],

    LoggerInterface::class => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $fileRotator = $container->get(FileRotatorInterface::class);

        $fileTarget = new FileTarget(
            $aliases->get('@runtime/logs/app.log'),
            $fileRotator
        );

        $fileTarget->setLevels(
            [
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::INFO,
                LogLevel::DEBUG
            ]
        );

        return new Logger([
            'file' => $fileTarget,
        ]);
    },

    Profiler::class => [
        '__class' => Profiler::class,
        '__construct()' => [
            Reference::to(LoggerInterface::class)
        ]
    ],

    Connection::class => static function (ContainerInterface $container) {
        $connection = new Connection(
            $container->get(\Yiisoft\Cache\CacheInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(Profiler::class),
            [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'dbname' => 'yiitest',
                'port' => '3306',
                'fixture' => dirname(__dir__) .  '/tests/data/mysql.sql'
            ]
        );

        $connection->setUsername('root');
        $connection->setPassword('root');

        return $connection;
    },
];
