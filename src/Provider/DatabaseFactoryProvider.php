<?php

declare(strict_types=1);

namespace Yiisoft\Db\Provider;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Db\Factory\DatabaseFactory;
use Yiisoft\Db\Factory\LoggerFactory;
use Yiisoft\Db\Factory\ProfilerFactory;
use Yiisoft\Db\Factory\QueryCacheFactory;
use Yiisoft\Db\Factory\SchemaCacheFactory;

final class DatabaseFactoryProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        DatabaseFactory::initialize($container);
        LoggerFactory::initialize($container);
        ProfilerFactory::initialize($container);
        QueryCacheFactory::initialize($container);
        SchemaCacheFactory::initialize($container);
    }
}
