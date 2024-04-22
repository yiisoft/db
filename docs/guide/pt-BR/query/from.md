# From

O método `Yiisoft\Db\Query\Query::from()` especifica o fragmento `FROM` de uma **instrução SQL**.

Por exemplo, o código a seguir selecionará todas as colunas da tabela `user`.

```php
$query->from('{{%user}}');
```

O SQL equivalente é:

```sql
SELECT * FROM `user`
```

Você pode especificar **tabelas** para selecionar como uma string ou uma array.
Os nomes das tabelas podem conter **prefixos de esquema e/ou aliases de tabela**, como você faz ao escrever **instruções SQL brutas**.

```php
$query->from(['{{public.%user}} u', '{{public.%post}} p']);

// equal to:

$query->from('{{public.%user}} u, {{public.%post}} p');
```

> Dica: Prefira o formato array, pois deixa menos espaço para erros e é mais limpo no geral.

Se estiver usando o formato de array, você também poderá especificar os aliases da tabela nas chaves da array, como a seguir.

```php
$query->from(['u' => '{{public.%user}}', 'p' => '{{public.%post}}']);
```
