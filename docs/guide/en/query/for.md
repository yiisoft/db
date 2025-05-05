# For

The `FOR` clause allows you to specify table locking behavior during a SELECT statement. It's commonly used to control 
concurrent access to data in multi-user database environments.

The `Yiisoft\Db\Query\Query` class provides three methods to specify the `FOR` fragment of a SQL query:

- `for()` - sets the `FOR` part of the query;
- `addFor()` - adds more `FOR` parts to the existing ones;
- `setFor()` - overwrites the `FOR` part of the query.
