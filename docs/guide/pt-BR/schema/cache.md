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

## Desabilitando o cache de esquema em desenvolvimento

Em ambientes de desenvolvimento, você pode querer desabilitar o cache de esquema para sempre obter as informações
mais recentes do banco de dados. Isso é útil quando você está alterando frequentemente a estrutura do banco de dados.

Você pode conseguir isso usando `NullCache` do [yiisoft/cache](https://github.com/yiisoft/cache).
`NullCache` não armazena nada em cache enquanto ainda implementa a `CacheInterface` PSR-16.

Crie um arquivo `config/dev/di/cache.php`:

```php
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\NullCache;

return [
    CacheInterface::class => NullCache::class,
];
```

> Nota: Lembre-se de mudar para uma implementação de cache real (como `FileCache`, `ArrayCache`, etc.)
> em produção para melhor desempenho.

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
