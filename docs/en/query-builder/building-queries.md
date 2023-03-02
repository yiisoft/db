# Building queries

To build a `Yiisoft\Db\Query\Query` object, you call different query building methods to specify different parts of a **SQL query**. The names of these methods resemble the **SQL keywords** used in the corresponding parts of the **SQL statement**. For example, to specify the **FROM** part of a **SQL query**, you would call the `Yiisoft\Db\Query\Query::from()` method. All the query building methods return the query object itself, which allows you to chain multiple calls together.

In the following, we will describe the usage of each query building method.

1. [Select](/docs/en/query/select.md)
2. [From](/docs/en/query/from.md)
3. [Where](/docs/en/query/where.md)
   - [String format](/docs/en/query/where.md#string-format)
   - [Hash format](/docs/en/query/where.md#hash-format)
   - [Operator format](/docs/en/query/where.md#operator-format)
   - [Object format](/docs/en/query/where.md#object-format)
   - [Appending conditions](/docs/en/query/where.md#appending-conditions)
   - [Filter conditions](/docs/en/query/where.md#filter-conditions)
