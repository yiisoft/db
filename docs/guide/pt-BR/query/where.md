# Where

O método `Yiisoft\Db\Query\Query::where()` especifica o fragmento `WHERE` de uma consulta SQL.
Você pode usar um dos quatro formatos para especificar uma condição `WHERE`.

- formato string, `status=1`.
- formato hash, `['status' => 1, 'type' => 2]`.
- formato array, `['like', 'name', 'test']`.
- formato objeto, `new LikeCondition('name', 'LIKE', 'test')`.

## Formato string

O formato string é melhor usado para especificar condições básicas ou se você precisar usar funções integradas do SGBD.
Funciona como se você estivesse escrevendo um SQL bruto.

Por exemplo, o código a seguir selecionará todos os usuários cujo status seja 1.

```php
$query->where('status = 1');

// or use parameter binding to bind dynamic parameter values
$query->where('status = :status', [':status' => $status]);

// raw SQL using MySQL "YEAR()" function on a date field
$query->where('YEAR(somedate) = 2015');
```

Não incorpore variáveis diretamente na condição como a seguir, especialmente se os valores das variáveis vierem
das entradas do usuário final, porque isso tornará seu aplicativo sujeito a ataques de injeção de SQL.

```php
// Dangerous! Don't do this unless you are certain $status must be an integer.
$query->where("status = $status");
```

Ao usar a ligação de parâmetros, você pode chamar `Yiisoft\Db\Query\Query::params()` ou `Yiisoft\Db\Query\Query::addParams()`
e passar os parâmetros como um argumento separado.

```php
$query->where('status = :status')->addParams([':status' => $status]);
```

Tal como acontece com todos os lugares onde o SQL bruto está envolvido,
você pode usar a sintaxe de quoting do DBMS para nomes de tabelas e colunas ao escrever condições em formato de string.

## Formato hash

O formato hash é melhor usado para especificar muitas subcondições concatenadas com `AND`, cada uma sendo uma simples afirmação de igualdade.
É escrito como um array cujas chaves são nomes de colunas e valores são seus valores correspondentes.

```php
$query->where(['status' => 10, 'type' => null, 'id' => [4, 8, 15]]);
```

A parte relevante do SQL é:

```sql
WHERE (`status` = 10) AND (`type` IS NULL) AND (`id` IN (4, 8, 15))
```

Como você pode ver, o construtor de consultas é inteligente o suficiente para lidar com valores nulos ou arrayes.

Você também pode usar subconsultas em formato hash como a seguir.

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$userQuery = $db->select('id')->from('user');
$query->where(['id' => $userQuery]);
```

A parte relevante do SQL é:

```sql
WHERE `id` IN (SELECT `id` FROM `user`)
```

Usando o formato hash, o Yii DB aplica internamente a vinculação de parâmetros para valores, portanto, em contraste com o formato string,
aqui você não precisa adicionar parâmetros manualmente.

Entretanto, observe que o Yii DB nunca escapa dos nomes das colunas, então se você passar uma variável obtida do lado do usuário como uma coluna
nome sem mais verificações, o aplicativo ficará vulnerável a ataques de injeção de SQL.

Para manter o aplicativo seguro, não use variáveis como nomes de colunas ou filtre variáveis com listas de permissões.

Por exemplo, o código a seguir é vulnerável.

```php
// Vulnerable code:
$column = $request->get('column');
$value = $request->get('value');
$query->where([$column => $value]);
// $value is safe, but the $column name won't be encoded!
```

## Formato operador

O formato operador permite especificar condições arbitrárias de forma programática. Tem a seguinte sintaxe:

```php
['operator', 'operand1', 'operand2', ...]
```

Onde cada operando pode ser especificado em formato de string, formato hash ou formato operador recursivamente,
enquanto o operador pode ser um dos seguintes:

### and

Os operandos devem ser concatenados usando `and`.

Por exemplo, `['and', 'id=1', 'id=2']` irá gerar `id=1 AND id=2`.

Se um operando for um array, ele será convertido em uma string usando as regras descritas aqui.

Por exemplo, `['and', 'type=1', ['or', 'id=1', 'id=2']]` irá gerar `type=1 AND (id=1 OR id=2)`.

> Nota: O método não fará nenhuma citação ou escape.

### or

Semelhante ao operador `and`, exceto que os operandos são concatenados usando `or`.

### not

Requer apenas 1 operando, que será encapsulado em `NOT()`.

Por exemplo, `['not', 'id=1']` irá gerar `NOT (id=1)`.

Operando também pode ser um array para descrever muitas expressões.

Por exemplo `['not', ['status' => 'draft', 'name' => 'example']]` irá gerar `NOT ((status='draft') AND (name='example'))`.

### between

O operando 1 deve ser o nome da coluna e os operandos 2 e 3 devem ser os valores inicial e final do intervalo em que a coluna está.

Por exemplo, `['between', 'id', 1,10]` irá gerar `id BETWEEN 1 AND 10`.

Caso você precise construir uma condição onde o valor esteja entre duas colunas `(like 11 BETWEEN min_id AND max_id)`,

você deve usar `Yiisoft\Db\QueryBuilder\Condition\BetweenColumnsCondition`.

### not between

Semelhante a `between` exceto que `BETWEEN` é substituído por `NOT BETWEEN` na condição gerada.

### in

O operando 1 deve ser uma coluna ou expressão de banco de dados.
O operando 2 pode ser um array ou um `Yiisoft\Db\Query\Query`.

Isso irá gerar uma condição `IN`.
Se o operando 2 for um array, ele representará o intervalo de valores que a coluna ou expressão do banco de dados deve ter;
Se o operando 2 for um objeto `Yiisoft\Db\Query\Query`, uma subconsulta será gerada e usada como intervalo da coluna ou expressão do banco de dados.

Por exemplo, `['in', 'id', [1, 2, 3]]` irá gerar o id `IN (1, 2, 3)`.

O método citará o nome da coluna e os valores de escape no intervalo.
O operador in também oferece suporte a colunas compostas.

Neste caso, o operando 1 deve ser um array de colunas,
enquanto o operando 2 deve ser um array de arrays ou um objeto `Yiisoft\Db\Query\Query` representando o intervalo das colunas.

Por exemplo, `['in', ['id', 'name'], [['id' => 1, 'name' => 'John Doe']]]` irá gerar `(id, name) IN ( (1, 'John Doe'))`.

### not in

Semelhante ao operador in, exceto que `IN` é substituído por `NOT IN` na condição gerada.

### like

O operando 1 deve ser uma coluna ou expressão de banco de dados e o operando 2 deve ser uma string ou array representando os valores
que a coluna ou expressão do banco de dados deve ter.

Por exemplo, `['like', 'name', 'tester']` gerará `name LIKE '%tester%'`.

Quando o intervalo de valores é fornecido como uma array, muitos predicados `LIKE` serão gerados e concatenados usando `AND`.

Por exemplo, `['like', 'name', ['test', 'sample']]` irá gerar `name LIKE '%test%' AND name LIKE '%sample%'`.

Você também pode fornecer um terceiro operando opcional para especificar como escapar de caracteres especiais nos valores.
O operando deve ser uma array de mapeamentos de caracteres especiais para suas contrapartes de escape.

Se este operando não for fornecido, um mapeamento de escape padrão será usado.

Você pode usar false ou um array vazio para indicar que os valores já foram escapados e nenhum escape deve ser aplicado.

> Nota: Ao usar um mapeamento de escape (ou o terceiro operando não for fornecido),
> os valores estarão automaticamente dentro de um par de caracteres percentuais.

> Nota: Ao usar o PostgreSQL, você também pode usar `ilike` em vez de `like` para correspondência sem distinção entre maiúsculas e minúsculas.

### or like

Semelhante ao operador `like` exceto que `OR` é usado para concatenar os predicados `LIKE` quando o segundo
operando é uma array.

### not like

Semelhante ao operador `like`, exceto que `LIKE` é substituído por `NOT LIKE` na condição gerada.

### or not like

Semelhante ao operador `not like`, exceto que `OR` é usado para concatenar os predicados `NOT LIKE`.

### exists

Requer um operando que deve ser uma instância de `Yiisoft\Db\Query\Query` representando a subconsulta.
Ele construirá uma expressão `EXISTS` (subconsulta).

## not exists

Semelhante ao operador `exists` e cria uma expressão `NOT EXISTS` (subconsulta).

### comparison

`>`, `<=` ou qualquer outro operador de banco de dados válido que receba dois operandos: o primeiro operando deve ser um `nome da coluna` enquanto
o segundo operando é um `valor`. Por exemplo, `['>', 'age', 10]` irá gerar `age > 10`.

Usando o formato operador, o Yii DB usa internamente a vinculação de parâmetros para valores, portanto, em contraste com o formato de string,
aqui você não precisa adicionar parâmetros manualmente.

Entretanto, observe que o Yii DB nunca escapa os nomes das colunas, então se você passar uma variável como nome da coluna, a aplicação irá
provavelmente se tornar vulnerável a ataques de injeção de SQL.

Para manter o aplicativo seguro, não use variáveis como nomes de colunas ou filtre variáveis em relação à lista de permissões.
Caso você precise obter um nome de coluna do usuário, por exemplo, o código a seguir é vulnerável.

```php
// Vulnerable code:
$column = $request->get('column');
$value = $request->get('value');
$query->where(['=', $column, $value]);
// $value is safe, but $column name won't be encoded!
```

## Formato objeto

O formato objeto é o modo mais poderoso, porém mais complexo de definir condições.
Você também precisa usá-lo se quiser construir sua própria abstração sobre o construtor de consultas
ou se você deseja implementar suas próprias condições complexas.

Instâncias de classes de condição são imutáveis.
Seu único propósito é armazenar dados de condições e fornecer getters para construtores de condições.
O construtor de condições é uma classe que contém a lógica que transforma os dados armazenados na condição na expressão SQL.

Internamente, os formatos descritos são convertidos implicitamente para o formato de objeto antes da construção do SQL bruto,
então é possível combinar formatos em uma única condição:

```php
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\QueryBuilder\Condition\OrCondition;
use Yiisoft\Db\Query\Query;

/** @var Query $query */

$query->andWhere(
    new OrCondition(
        [
            new InCondition('type', 'in', $types),
            ['like', 'name', '%good%'],
            'disabled=false',
        ],
    ),
);
```

A conversão do formato operador para o formato objeto é realizada de acordo
com a propriedade `Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder::conditionClasses`
que mapeia nomes de operadores para nomes de classes representativos.

- `AND`, `OR` => `Yiisoft\Db\QueryBuilder\Condition\ConjunctionCondition`.
- `NOT` => `Yiisoft\Db\QueryBuilder\Condition\NotCondition`.
- `IN`, `NOT IN` => `Yiisoft\Db\QueryBuilder\Condition\InCondition`.
- `BETWEEN`, `NOT BETWEEN` => `Yiisoft\Db\QueryBuilder\Condition\BetweenCondition`.

## Anexando condições

Você pode usar `Yiisoft\Db\Query\Query::andWhere()` ou `Yiisoft\Db\Query\Query::orWhere()` para anexar mais condições
para um já existente. Você pode chamá-los várias vezes para acrescentar muitas condições. Isso é útil para lógica condicional,
por exemplo:

```php
$status = 10;
$search = 'yii';

$query->where(['status' => $status]);

if ($search !== '') {
    $query->andWhere(['like', 'title', $search]);
}
```

Se $search não estiver vazio, a seguinte condição `WHERE` será gerada:

```sql
WHERE (`status` = 10) AND (`title` LIKE '%yii%')
```

## Condições de filtro

Ao criar condições `WHERE` com base nas informações dos usuários finais, você geralmente deseja ignorar os valores de entrada
que estão vazios.

Por exemplo, em um formulário de pesquisa que permite pesquisar por nome de usuário e e-mail, você gostaria
de ignorar a condição de nome de usuário/e-mail se o usuário não inseriu nada no campo de entrada correspondente.

Você pode atingir esse objetivo usando o método `Yiisoft\Db\Query\Query::filterWhere()`:

```php
// $username and $email are from user inputs
$query->filterWhere(['username' => $username, 'email' => $email]);
```

A única diferença entre `Yiisoft\Db\Query\Query::filterWhere()` e `Yiisoft\Db\Query\Query::where()`
é que o primeiro irá ignorar os valores vazios fornecidos na condição em formato hash.

Então, se `$email` estiver vazio enquanto `$username` não estiver,
o código acima resultará na condição SQL `WHERE username=:username`.

> Nota: Um valor é considerado vazio se for `nulo`, um array vazio, uma string vazia ou uma string contendo
> apenas espaços em branco.

Assim como `Yiisoft\Db\Query\Query::andWhere()` e `Yiisoft\Db\Query\Query::orWhere()`,
você pode usar `Yiisoft\Db\Query\Query::andFilterWhere()`
e `Yiisoft\Db\Query\Query::orFilterWhere()` para anexar mais condições de filtro ao existente.

Além disso, existe `Yiisoft\Db\Query\Query::andFilterCompare()` que pode determinar o operador de forma inteligente com base
no que está no valor.

```php
$query
    ->andFilterCompare('name', 'John Doe');
    ->andFilterCompare('rating', '>9');
    ->andFilterCompare('value', '<=100');
```

Você também pode especificar o operador explicitamente:

```php
$query->andFilterCompare('name', 'Doe', 'like');
```
