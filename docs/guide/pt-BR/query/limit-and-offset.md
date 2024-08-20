# Limit and offset (Limite e deslocamento)

Os métodos `\Yiisoft\Db\Query\Query::limit()` e `\Yiisoft\Db\Query\Query::offset()` especificam
os fragmentos `LIMIT` e `OFFSET` de uma consulta SQL.

Por exemplo, o código a seguir criará uma consulta que retornará apenas 10 registros a partir do 20º.

```php
$query->limit(10)->offset(20);
```

A parte relevante do SQL é:

```sql
LIMIT 10 OFFSET 20
```

A consulta ignora limite ou deslocamento inválido, como um valor negativo.

> Nota: Para DBMS que não suportam `LIMIT` e `OFFSET` como `MSSQL`, o construtor de consultas irá gerar uma
> declaração SQL que emula esse comportamento.
