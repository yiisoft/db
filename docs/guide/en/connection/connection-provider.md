# Define the Database Connection using ConnectionProvider

For some cases when the database connection cannot be obtained via dependency injection, you can use the `ConnectionProvider`
class to define the DB connection and then get it where needed.

## Bootstrap configuration

Add the following code to the configuration file, for example, in `config/common/bootstrap.php`:

```php
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    static function (ContainerInterface $container): void {
        ConnectionProvider::set($container->get(ConnectionInterface::class));
    }
];
```

## Usage

You can get the defined connection using the `ConnectionProvider::get()` method:

```php
use Yiisoft\Db\Connection\ConnectionProvider;

$db = ConnectionProvider::get(); // Gets the default connection
$db2 = ConnectionProvider::get('db2'); // Gets the connection by name
```
