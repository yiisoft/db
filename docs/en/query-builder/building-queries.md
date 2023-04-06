# Building queries

To build a `Yiisoft\Db\Query\Query` object, you call various query building methods to specify different parts
of a SQL query.

The names of these methods resemble the **SQL keywords** used in the corresponding parts of the **SQL statement**.
For example, to specify the `FROM` part of a **SQL query**, you would call the `Yiisoft\Db\Query\Query::from()` method.
All the query building methods return the query object itself, which allows you to chain many calls together.

- [Select](/docs/en/query/select.md)
- [From](/docs/en/query/from.md)
- [Where](/docs/en/query/where.md)
  - [String format](/docs/en/query/where.md#string-format)
  - [Hash format](/docs/en/query/where.md#hash-format)
  - [Operator format](/docs/en/query/where.md#operator-format)
    - [and](/docs/en/query/where.md#and)
    - [or](/docs/en/query/where.md#or)
    - [not](/docs/en/query/where.md#not)
    - [between](/docs/en/query/where.md#between)
    - [not between](/docs/en/query/where.md#not-between)
    - [in](/docs/en/query/where.md#in)
    - [not in](/docs/en/query/where.md#not-in)
    - [like](/docs/en/query/where.md#like)
    - [or like](/docs/en/query/where.md#or-like)
    - [not like](/docs/en/query/where.md#not-like)
    - [or not like](/docs/en/query/where.md#or-not-like)
    - [exists](/docs/en/query/where.md#exists)
    - [not exists](/docs/en/query/where.md#not-exists)
    - [comparison](/docs/en/query/where.md#comparison)
  - [Object format](/docs/en/query/where.md#object-format)
  - [Appending conditions](/docs/en/query/where.md#appending-conditions)
  - [Filter conditions](/docs/en/query/where.md#filter-conditions)
- [OrderBy](/docs/en/query/order-by.md)
- [GroupBy](/docs/en/query/group-by.md)
- [Having](/docs/en/query/having.md)
- [Limit and Offset](/docs/en/query/limit-and-offset.md)
- [Join](/docs/en/query/join.md)
- [Union](/docs/en/query/union.md)
- [WithQuery](/docs/en/query/with-query.md)
