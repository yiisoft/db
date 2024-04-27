# Order by

O método `\Yiisoft\Db\Query\Query::orderBy()` especifica o fragmento `ORDER BY` de uma consulta SQL.

Por exemplo, o código a seguir irá gerar uma consulta que ordena os resultados pela coluna `id` em ordem crescente
e pela coluna `nome` em ordem decrescente.

```php
$query->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC]);
```

A parte relevante do SQL é:

```sql
ORDER BY `id` ASC, `name` DESC
```

As chaves do array são nomes de colunas, enquanto os valores do array são as direções `ORDER BY` correspondentes.
A constante PHP `SORT_ASC` especifica a classificação ascendente e `SORT_DESC` especifica a classificação decrescente.

Se `ORDER BY` envolver apenas nomes de colunas simples, você pode especificá-lo usando uma string, assim como faz ao escrever
instruções SQL brutas.

Por exemplo, o código a seguir irá gerar uma consulta que ordena os resultados pela coluna `id` em ordem crescente
e pela coluna `nome` em ordem decrescente.

```php
$query->orderBy('id ASC, name DESC');
```

> Dica: Prefira o formato array se `ORDER BY` envolver alguma expressão do banco de dados.

Você pode chamar `\Yiisoft\Db\Query\Query::addOrderBy()` para adicionar mais colunas ao fragmento `ORDER BY`.

Por exemplo, o código a seguir irá gerar uma consulta que ordena os resultados pela coluna `id` em ordem crescente
e pela coluna `nome` em ordem decrescente.

```php
$query->orderBy('id ASC')->addOrderBy('name DESC');
```
