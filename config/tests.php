<?php

declare(strict_types=1);

return [
    \Psr\Container\ContainerInterface::class => static function (\Psr\Container\ContainerInterface $container) {
        return $container;
    },

    \Yiisoft\Aliases\Aliases::class => [
        '@root' => dirname(__DIR__, 1),
        '@runtime' => '@root/tests/data/runtime',
    ],

    \Yiisoft\Cache\CacheInterface::class => static function (\Psr\Container\ContainerInterface $container) {
        return new \Yiisoft\Cache\Cache(new \Yiisoft\Cache\ArrayCache());
    },

    \Yiisoft\Log\Target\File\FileRotatorInterface::class => static function () {
        return new \Yiisoft\Log\Target\File\FileRotator(10);
    },

    \Psr\Log\LoggerInterface::class => static function (\Psr\Container\ContainerInterface $container) {
        $aliases = $container->get(\Yiisoft\Aliases\Aliases::class);
        $fileRotator = $container->get(\Yiisoft\Log\Target\File\FileRotatorInterface::class);

        $fileTarget = new \Yiisoft\Log\Target\File\FileTarget(
            $aliases->get('@runtime/logs/app.log'),
            $fileRotator
        );

        $fileTarget->setLevels(
            [
                \Psr\Log\LogLevel::EMERGENCY,
                \Psr\Log\LogLevel::ERROR,
                \Psr\Log\LogLevel::WARNING,
                \Psr\Log\LogLevel::INFO,
                \Psr\Log\LogLevel::DEBUG
            ]
        );

        return new \Yiisoft\Log\Logger([
            'file' => $fileTarget,
        ]);
    },

    \Yiisoft\Profiler\Profiler::class => static function (\Psr\Container\ContainerInterface $container) {
        return new \Yiisoft\Profiler\Profiler($container->get(\Psr\Log\LoggerInterface::class));
    },
];
