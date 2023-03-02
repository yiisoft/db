# Building queries

To build a `Yiisoft\Db\Query\Query` object, you call different query building methods to specify different parts of a **SQL query**. The names of these methods resemble the **SQL keywords** used in the corresponding parts of the **SQL statement**. For example, to specify the **FROM** part of a **SQL query**, you would call the `Yiisoft\Db\Query\Query::from()` method. All the query building methods return the query object itself, which allows you to chain multiple calls together.

In the following, we will describe the usage of each query building method.

1. [select](/docs/en/query/select.md)
2. [from](/docs/en/query/from.md)
3. [where](/docs/en/query/where.md)
   - [string format](/docs/en/query/where.md#string-format)
   - [hash format](/docs/en/query/where.md#hash-format)
   - [operator format](/docs/en/query/where.md#operator-format)
   - [object format](/docs/en/query/where.md#object-format)
   - [appending conditions](/docs/en/query/where.md#appending-conditions)
   - [filter conditions](/docs/en/query/where.md#filter-conditions)
