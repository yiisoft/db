# Building queries

To build a `Yiisoft\Db\Query\Query` object, you call various query building methods to specify different parts
of a SQL query.

The names of these methods resemble the **SQL keywords** used in the corresponding parts of the **SQL statement**.
For example, to specify the `FROM` part of a **SQL query**, you would call the `Yiisoft\Db\Query\Query::from()` method.
All the query building methods return the query object itself, which allows you to chain many calls together.

- [Select](/docs/guide/en/query/select.md)
- [From](/docs/guide/en/query/from.md)
- [Where](/docs/guide/en/query/where.md)
  - [String format](/docs/guide/en/query/where.md#string-format)
  - [Hash format](/docs/guide/en/query/where.md#hash-format)
  - [Operator format](/docs/guide/en/query/where.md#operator-format)
    - [and](/docs/guide/en/query/where.md#and)
    - [or](/docs/guide/en/query/where.md#or)
    - [not](/docs/guide/en/query/where.md#not)
    - [between](/docs/guide/en/query/where.md#between)
    - [not between](/docs/guide/en/query/where.md#not-between)
    - [in](/docs/guide/en/query/where.md#in)
    - [not in](/docs/guide/en/query/where.md#not-in)
    - [like](/docs/guide/en/query/where.md#like)
    - [or like](/docs/guide/en/query/where.md#or-like)
    - [not like](/docs/guide/en/query/where.md#not-like)
    - [or not like](/docs/guide/en/query/where.md#or-not-like)
    - [exists](/docs/guide/en/query/where.md#exists)
    - [not exists](/docs/guide/en/query/where.md#not-exists)
    - [comparison](/docs/guide/en/query/where.md#comparison)
  - [Object format](/docs/guide/en/query/where.md#object-format)
  - [Appending conditions](/docs/guide/en/query/where.md#appending-conditions)
  - [Filter conditions](/docs/guide/en/query/where.md#filter-conditions)
- [OrderBy](/docs/guide/en/query/order-by.md)
- [GroupBy](/docs/guide/en/query/group-by.md)
- [Having](/docs/guide/en/query/having.md)
- [Limit and Offset](/docs/guide/en/query/limit-and-offset.md)
- [Join](/docs/guide/en/query/join.md)
- [Union](/docs/guide/en/query/union.md)
- [WithQuery](/docs/guide/en/query/with-query.md)
