# Building queries

To build a `Yiisoft\Db\Query\Query` object, you call various query building methods to specify different parts
of a SQL query.

The names of these methods resemble the **SQL keywords** used in the corresponding parts of the **SQL statement**.
For example, to specify the `FROM` part of a **SQL query**, you would call the `Yiisoft\Db\Query\Query::from()` method.
All the query building methods return the query object itself, which allows you to chain many calls together.

- [Select](../query/select.md)
- [From](../query/from.md)
- [Where](../query/where.md)
  - [String format](../query/where.md#string-format)
  - [Hash format](../query/where.md#hash-format)
  - [Operator format](../query/where.md#operator-format)
    - [and](../query/where.md#and)
    - [or](../query/where.md#or)
    - [not](../query/where.md#not)
    - [between](../query/where.md#between)
    - [not between](../query/where.md#not-between)
    - [in](../query/where.md#in)
    - [not in](../query/where.md#not-in)
    - [like](../query/where.md#like)
    - [or like](../query/where.md#or-like)
    - [not like](../query/where.md#not-like)
    - [or not like](../query/where.md#or-not-like)
    - [exists](../query/where.md#exists)
    - [not exists](../query/where.md#not-exists)
    - [comparison](../query/where.md#comparison)
  - [Object format](../query/where.md#object-format)
  - [Appending conditions](../query/where.md#appending-conditions)
  - [Filter conditions](../query/where.md#filter-conditions)
- [OrderBy](../query/order-by.md)
- [GroupBy](../query/group-by.md)
- [Having](../query/having.md)
- [Limit and Offset](../query/limit-and-offset.md)
- [Join](../query/join.md)
- [Union](../query/union.md)
- [WithQuery](../query/with-query.md)
