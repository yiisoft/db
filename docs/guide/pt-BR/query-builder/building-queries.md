# Construindo consultas

Para construir um objeto `Yiisoft\Db\Query\Query`, você chama vários métodos de construção de consulta para especificar diferentes partes
de uma consulta SQL.

Os nomes desses métodos são semelhantes às **palavras-chaves SQL** usadas nas partes correspondentes da **instrução SQL**.
Por exemplo, para especificar a parte `FROM` de uma **consulta SQL**, você chamaria o método `Yiisoft\Db\Query\Query::from()`.
Todos os métodos de construção de consulta retornam o próprio objeto de consulta, o que permite encadear muitas chamadas.

- [Select](../query/select.md)
- [From](../query/from.md)
- [Where](../query/where.md)
  - [Formato string](../query/where.md#formato-string)
  - [Formato hash](../query/where.md#formato-hash)
  - [Formato operador](../query/where.md#formato-operador)
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
  - [Formato objeto](../query/where.md#formato-objeto)
  - [Anexando condições](../query/where.md#anexando-condições)
  - [Condições de filtro](../query/where.md#condições-de-filtro)
- [OrderBy](../query/order-by.md)
- [GroupBy](../query/group-by.md)
- [Having](../query/having.md)
- [Limit and Offset](../query/limit-and-offset.md)
- [Join](../query/join.md)
- [Union](../query/union.md)
- [WithQuery](../query/with-query.md)
