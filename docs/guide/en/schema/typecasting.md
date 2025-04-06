# Type casting values

Type casting is the process of converting a value from one data type to another. In the context of the database,
type casting is used to ensure that values are saved and retrieved in the correct type.

```mermaid
flowchart LR
    phpType[PHP Type]
    dbType[Database Type]
    
    phpType --> dbType
    dbType --> phpType
```

## Casting values to be saved in the database

When saving a value to the database, the value must be in the correct type. For example, if saving a value to a column
that is of type `bit`, the value must be an `integer` or `string` depends on DBMS.

To ensure that the value is saved in the correct type, `ColumnInterface::dbTypecast()` method can be used to cast 
the value. Majority of the DB library methods, such as `CommandInterface::insert()`, automatically convert the type.

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand();
$command->insert('customer', [
    'name' => 'John Doe',
    'is_active' => true,
]);
$command->execute();
```

In the example above, the value of `is_active` is a `boolean`, but the column `is_active` can be of type `bit`.
The `CommandInterface::insert()` method will automatically cast the value to the correct type.

## Casting values retrieved from the database

When you retrieve a value from the database, the value can be returned in a different type than you expect.
For example, a value that is stored as a `numeric(5,2)` in the database will be returned as a `string`. This is because 
the database driver does not convert some data types when retrieves values.

To ensure that the value is returned in the correct type, you can use `ColumnInterface::phpTypecast()` method to cast 
the value, in the example above, to a `float`.

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM {{customer}} WHERE id = 1');

$row = $command->queryOne();
$isActive = $row['is_active'];

// Cast the value to the correct type
$isActive = $db->getTableSchema('customer')->getColumn('is_active')->phpTypecast($isActive);
```

In the example above, the value of `is_active` can be retrieved from the database as a `bit`, but the correct PHP type 
is `boolean`. The `ColumnInterface::phpTypecast()` method is used to cast the value to the correct type.

## Custom type casting

To implement custom type casting you need to extend the `AbstractColumn` class and override the `dbTypecast()` 
and `phpTypecast()` methods.

For example, in Postgres database, the `point` type is represented as a string in the format `(x,y)`. To cast the value
to a `Point` class, you can create a custom column class and override the `dbTypecast()` and `phpTypecast()`.

```php
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\AbstractColumn;

final class PointColumn extends AbstractColumn
{
    /**
     * @var string The default column abstract type
     */
    protected const DEFAULT_TYPE = 'point';

    /**
    * @param ExpressionInterface|Point|string|null $value
    */
    public function dbTypecast(mixed $value): ExpressionInterface|string|null
    {
        if ($value instanceof Point) {
            return new Expression('(:x,:y)', ['x' => $value->getX(), 'y' => $value->getY()]);
        }
    
        return $value;
    }

    /**
    * @param string|null  $value
    */
    public function phpTypecast(mixed $value): Point|null
    {
        if (is_string($value)) {
            [$x, $y] = explode(',', substr($value, 1, -1));

            return new Point((float) $x, (float) $y);
        }
    
        return $value;
    }
}

class Point
{
    public function __construct(
        private float $x,
        private float $y,
    ) {
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }
}
```

Then use the custom column class in the database connection configuration.

```php
use Yiisoft\Db\Pgsql\Column\ColumnFactory;
use Yiisoft\Db\Pgsql\Connection;

$columnFactory = new ColumnFactory(
    'columnClassMap' => [
        // It is necessary to define the column class map for the custom abstract type
        // abstract type => class name
        'point' => PointColumn::class,
    ],
    'typeMap' => [
        // It is necessary to define the type map for the database type
        // database type => abstract type
        'point' => 'point',
    ],
);

// Create a database connection with the custom column factory
$db = new Connection($pdoDriver, $schemaCache, $columnFactory);
```

In the example above, the `PointColumn` class is used to cast the `point` database type to the `Point` class.
The `Point` class is used to represent the `point` type as an object with `x` and `y` properties.

> [!WARNING]
> If you use different custom type casting for different database connections, you need also use different schema 
> cache for such connections.

## Lazy type casting

Lazy type casting is a way to defer the type casting of a value until it is accessed. This can be useful when you want
to avoid the overhead of type casting for values that are not used.

Here is an example how to configure lazy type casting for the `array`, `json` and `structured` database types.

```php
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Pgsql\Column\ColumnFactory;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Schema\Column\ArrayLazyColumn;
use Yiisoft\Db\Schema\Column\StructuredLazyColumn;
use Yiisoft\Db\Schema\Data\JsonLazyArray;

$columnFactory = new ColumnFactory(
    'columnClassMap' => [
        ColumnType::ARRAY => ArrayLazyColumn::class, // converts values to `LazyArray` objects 
        ColumnType::JSON => JsonLazyColumn::class, // converts values to `JsonLazyArray` objects
        ColumnType::STRUCTURED => StructuredLazyColumn::class, // converts values to `StructuredLazyArray` objects
    ],
);

// Create a database connection with the custom column factory
$db = new Connection($pdoDriver, $schemaCache, $columnFactory);

/** @var JsonLazyArray $tags `tags` column is of database type `json` */
$tags = $db->getTableSchema('customer')->getColumn('tags')->phpTypecast($row['tags']);

foreach ($tags as $tag) {
    echo $tag;
}
```

## Structured data types

Some databases support structured data types, such as `composite` types in Postgres. To cast a structured data type to
a custom class, you need to create a column class which extends `AbstractStructuredColumn` and override 
the `phpTypecast()` method.

For example if `currency_money` is a defined composite type in Postgres as follows:

```sql
CREATE TYPE currency_money AS (
    value DECIMAL(10,2),
    currency_code CHAR(3)
);
```

you can create `MyStructuredColumn` column class to cast the value to `CurrencyMoney` class.

```php
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Data\StructuredParser;

class MyStructuredColumn extends AbstractStructuredColumn
{
    /**
    * @param string|null $value
    */
    public function phpTypecast(mixed $value): CurrencyMoney|StructuredLazyArray|null
    {
        if (is_string($value)) {
            $value = new StructuredLazyArray($value, $this->getColumns());
        
            return match ($this->getDbType()) {
                'currency_money' => new CurrencyMoney(...$value->getValue()),
                default => $value,
            };
        }
        
        return $value;
    }
}

class CurrencyMoney implements \JsonSerializable, \IteratorAggregate
{
    public function __construct(
        private float $value,
        private string $currencyCode = 'USD',
    ) {
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * `JsonSerializable` interface is implemented to convert the object to a database representation.
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'currency_code' => $this->currencyCode,
        ];
    }
    
    /** 
     * Alternatively, `IteratorAggregate` interface can be implemented to convert the object to a database representation.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator([
            'value' => $this->value,
            'currency_code' => $this->currencyCode,
        ]);
    } 
}
```

Then use the column class in the database connection configuration.

```php
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Pgsql\Column\ColumnFactory;
use Yiisoft\Db\Pgsql\Connection;

$columnFactory = new ColumnFactory(
    'columnClassMap' => [
        ColumnType::STRUCTURED => MyStructuredColumn::class,
    ],
);

// Create a database connection with the custom column factory
$db = new Connection($pdoDriver, $schemaCache, $columnFactory);
```
