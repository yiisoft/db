# Construindo consultas

Para construir um objeto `Yiisoft\Db\Query\Query`, você chama vários métodos de construção de consulta para especificar diferentes partes
de uma consulta SQL.

Os nomes desses métodos são semelhantes às **palavras-chaves SQL** usadas nas partes correspondentes da **instrução SQL**.
Por exemplo, para especificar a parte `FROM` de uma **consulta SQL**, você chamaria o método `Yiisoft\Db\Query\Query::from()`.
Todos os métodos de construção de consulta retornam o próprio objeto de consulta, o que permite encadear muitas chamadas.

- [Select](/docs/guide/pt-BR/query/select.md)
- [From](/docs/guide/pt-BR/query/from.md)
- [Where](/docs/guide/pt-BR/query/where.md)
  - [Formato string](/docs/guide/pt-BR/query/where.md#formato-string)
  - [Formato hash](/docs/guide/pt-BR/query/where.md#formato-hash)
  - [Formato operador](/docs/guide/pt-BR/query/where.md#formato-operador)
    - [and](/docs/guide/pt-BR/query/where.md#and)
    - [or](/docs/guide/pt-BR/query/where.md#or)
    - [not](/docs/guide/pt-BR/query/where.md#not)
    - [between](/docs/guide/pt-BR/query/where.md#between)
    - [not between](/docs/guide/pt-BR/query/where.md#not-between)
    - [in](/docs/guide/pt-BR/query/where.md#in)
    - [not in](/docs/guide/pt-BR/query/where.md#not-in)
    - [like](/docs/guide/pt-BR/query/where.md#like)
    - [or like](/docs/guide/pt-BR/query/where.md#or-like)
    - [not like](/docs/guide/pt-BR/query/where.md#not-like)
    - [or not like](/docs/guide/pt-BR/query/where.md#or-not-like)
    - [exists](/docs/guide/pt-BR/query/where.md#exists)
    - [not exists](/docs/guide/pt-BR/query/where.md#not-exists)
    - [comparison](/docs/guide/pt-BR/query/where.md#comparison)
  - [Formato objeto](/docs/guide/pt-BR/query/where.md#formato-objeto)
  - [Anexando condições](/docs/guide/pt-BR/query/where.md#anexando-condições)
  - [Condições de filtro](/docs/guide/pt-BR/query/where.md#condições-de-filtro)
- [OrderBy](/docs/guide/pt-BR/query/order-by.md)
- [GroupBy](/docs/guide/pt-BR/query/group-by.md)
- [Having](/docs/guide/pt-BR/query/having.md)
- [Limit and Offset](/docs/guide/pt-BR/query/limit-and-offset.md)
- [Join](/docs/guide/pt-BR/query/join.md)
- [Union](/docs/guide/pt-BR/query/union.md)
- [WithQuery](/docs/guide/pt-BR/query/with-query.md)
