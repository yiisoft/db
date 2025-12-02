# Configurando o cache de esquema

As informações sobre o esquema do banco de dados necessário para o ORM vêm de
[Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) que o recupera do
servidor de banco de dados.

Para acesso mais rápido, [Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) armazena as
informações de esquema do banco de dados em [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php).

Quando o [Schema](https://github.com/yiisoft/db/blob/master/src/Schema/AbstractSchema.php) precisa
recuperar informações sobre o esquema do banco de dados, primeiro ele verifica o cache.

Você pode configurar [SchemaCache](https://github.com/yiisoft/db/blob/master/src/Cache/SchemaCache.php) para usar a
[Implementação de cache PSR-16](https://github.com/php-fig/simple-cache) de duas maneiras:

- Usar a ligação automática do [contêiner DI](https://github.com/yiisoft/di).
- Configurá-lo manualmente.

Os exemplos abaixo usam [yiisoft/cache](https://github.com/yiisoft/cache). Certifique-se de tê-lo instalado via [Composer](https://getcomposer.org)
usando `composer require yiisoft/cache`.

## Cache PSR-16 com conexão automática

Esta configuração é adequada se você quiser usar o mesmo driver de cache para todo o aplicativo.

Crie um arquivo `config/common/di/cache.php` para cache:

```php
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\File\FileCache;

/** @var array $params */

return [
    CacheInterface::class => [
        'class' => FileCache::class,
        '__construct()' => [
            'cachePath' => __DIR__ . '/../../runtime/cache',
        ],
    ],
];
```

O `SchemaCache` requer `CacheInterface` e o contêiner DI irá resolvê-lo automaticamente.

## Configuração manual de cache

Esta configuração é adequada se você quiser usar um driver de cache diferente para o esquema de cache.

Crie um arquivo `config/common/di/db-schema-cache.php` para cache:

```php
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        '__construct()' => [
            new FileCache(__DIR__ . '/../../runtime/cache'),
        ],
    ],
];
```

## Desabilitando o cache de esquema

Você pode desabilitar o cache de esquema definindo o parâmetro `enabled` como `false` no arquivo `config/params.php` da sua aplicação:

```php
return [
    // ...
    'yiisoft/db' => [
        'schema-cache' => [
            'enabled' => false,
        ],
    ],
];
```

Em seguida, use esse parâmetro na configuração do seu contêiner DI:

```php
use Yiisoft\Db\Cache\SchemaCache;

/** @var array $params */

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        'setEnabled()' => [$params['yiisoft/db']['schema-cache']['enabled']],
    ],
];
```
