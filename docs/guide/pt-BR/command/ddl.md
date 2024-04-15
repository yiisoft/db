# Comandos de linguagem de definição de dados (DDL)

Linguagem de definição de dados (DDL) é um conjunto de instruções SQL para definir a estrutura do banco de dados.

Instruções DDL são usadas para criar e alterar os objetos de banco de dados em um banco de dados.
Esses objetos podem ser tabelas, índices, visualizações, procedimentos armazenados, gatilhos e assim por diante.

## Tabelas

### Crie uma tabela

Para criar uma tabela, você pode usar o método `Yiisoft\Db\Command\CommandInterface::createTable()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createTable(
    '{{%customer}}',
     [
        'id' => 'pk',
        'name' => 'string(255) NOT NULL',
        'email' => 'string(255) NOT NULL',
        'status' => 'integer NOT NULL',
        'created_at' => 'datetime NOT NULL',
     ],
)->execute();
```

A biblioteca criará e executará automaticamente o SQL adequado ao banco de dados utilizado. Por exemplo, a conexão MSSQL
executará o seguinte SQL:

```sql
CREATE TABLE [customer] (
    [id] int IDENTITY PRIMARY KEY,
    [name] nvarchar(255) NOT NULL,
    [email] nvarchar(255) NOT NULL,
    [status] int NOT NULL,
    [created_at] datetime NOT NULL
)
```

E o seguinte SQL será executado no MySQL/MariaDB:

```sql
CREATE TABLE `customer` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `status` int(11) NOT NULL,
    `created_at` datetime(0) NOT NULL
)
```

### Apagar uma tabela

Para eliminar uma tabela e todos os seus dados, você pode usar o
método `Yiisoft\Db\Command\CommandInterface::dropTable()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropTable('{{%customer}}')->execute();
```

> Aviso: Todos os dados existentes serão excluídos.

### Truncar uma tabela

Para limpar apenas os dados de uma tabela sem remover a tabela você pode usar o
método `Yiisoft\Db\Command\CommandInterface::truncateTable()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->truncateTable('{{%customer}}')->execute();
```

> Aviso: Todos os dados existentes serão excluídos.

## Colunas

### Adicione uma nova coluna

Para adicionar uma nova coluna a uma tabela existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addColumn()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Column;

/** @var ConnectionInterface $db */
$db->createCommand()->addColumn(
    '{{%customer}}',
    'profile_id',
     new Column('integer')
)->execute();
```

### Alterar uma coluna

Para alterar uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::alterColumn()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Column;

/** @var ConnectionInterface $db */
$db->createCommand()->alterColumn(
    '{{%customer}}',
    'profile_id',
    new Column('integer')->notNull()
)->execute();
```

### Renomear uma coluna

Para renomear uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::renameColumn()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->renameColumn('{{%customer}}', 'profile_id', 'profile_id_new')->execute();
```

### Eliminar uma coluna

Para eliminar uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropColumn()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropColumn('{{%customer}}', 'profile_id')->execute();
```

### Adicionar um valor padrão a uma coluna

Para adicionar um valor padrão a uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addDefaultValue()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addDefaultValue('{{%customer}}', 'df-customer-name', 'name', 'John Doe')->execute();
```

### Remove o valor padrão de uma coluna

Para eliminar um valor padrão de uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropDefaultValue()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropDefaultValue('{{%customer}}', 'df-customer-name')->execute();
```

## Chaves

### Adicione uma chave primária

Para adicionar uma chave primária a uma tabela existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addPrimaryKey()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addPrimaryKey('{{%customer}}', 'pk-customer-id', 'id')->execute();
```

### Adicione uma chave estrangeira

Para adicionar uma chave estrangeira a uma tabela existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addForeignKey()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addForeignKey(
    '{{%customer}}',
    'fk-customer-profile_id',
    'profile_id',
    '{{%profile}}',
    'id',
    'CASCADE',
    'CASCADE'
)->execute();
```

### Remover uma chave primária

Para remover uma chave primária existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropPrimaryKey()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropPrimaryKey('{{%customer}}', 'pk-customer-id')->execute();
```

### Remover uma chave estrangeira

Para remover uma chave estrangeira existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropForeignKey()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropForeignKey('{{%customer}}', 'fk-customer-profile_id')->execute();
```

## Índices

### Adicione um índice

Para adicionar um índice a uma tabela existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::createIndex()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('{{%customer}}', 'idx-customer-name', 'name')->execute();
```

### Eliminar um índice

Para eliminar um índice existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropIndex()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropIndex('{{%customer}}', 'idx-customer-name')->execute();
```

### Adicionar índice exclusivo

Você pode criar um índice único especificando a opção `UNIQUE` no parâmetro `$indexType`, é suportado por todos SGBDs:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'UNIQUE')->execute();
```

> Info: índices exclusivos são índices que ajudam a manter a integridade dos dados, garantindo que nenhuma linha de dados em uma tabela tenha valores idênticos nos valores da chave.
> Quando você cria um índice exclusivo para uma tabela existente com dados, valores nas colunas ou expressões que compõem a
> chave de índice são verificadas quanto à exclusividade.

### Adicionar índice clusterizado

No MSSQL, você pode criar um índice clusterizado especificando a opção `CLUSTERED` no parâmetro `$indexType`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'CLUSTERED')->execute();
```

> Info: Um índice clusterizado é um índice que define a ordem física na qual os registros da tabela são armazenados em um banco de dados.
> Como só pode haver uma maneira pela qual os registros são armazenados fisicamente em uma tabela de banco de dados, só pode haver um
> índice clusterizado por tabela. Por padrão, um índice clusterizado é criado em uma coluna de chave primária.

### Adicionar índice não clusterizado

No MSSQL, você pode criar um índice não clusterizado especificando a opção `NONCLUSTERED` no parâmetro `$indexType`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'NONCLUSTERED')->execute();
```

> Info: Um índice não clusterizado também é usado para acelerar as operações de pesquisa. Ao contrário de um índice clusterizado, um índice não clusterizado não
> define fisicamente a ordem em que os registros são inseridos em uma tabela. Na verdade, um índice não clusterizado é armazenado em um
> local separado da tabela de dados.
>
> Um índice não clusterizado é como um índice de livro, localizado separadamente do conteúdo principal do livro. Como não está clusterizado
> os índices estão localizados em um local separado, pode haver vários índices não agrupados em cluster por tabela.

### Adicionar índice de texto completo (fulltext)

No MySQL e MariaDB, você pode criar um índice de texto completo especificando a opção `FULLTEXT` no parâmetro `$indexType`.

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->createIndex('test', 'idx_test_name', 'name', 'FULLTEXT')->execute();
```

> Info: índices de texto completo são criados em colunas baseadas em texto (colunas `CHAR`, `VARCHAR` ou `TEXT`) para acelerar consultas e operações DML
> nos dados contidos nessas colunas.
>
> Um índice de texto completo é definido como parte de uma instrução `CREATE TABLE` ou adicionado a uma tabela existente usando `ALTER TABLE` ou `CREATE INDEX`.

### Adicionar índice de bitmap

No `Oracle`, você pode criar um índice de bitmap especificando a opção `BITMAP` no parâmetro `$indexType`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$db->createCommand()->createIndex('test', 'idx_test_name', 'id', 'BITMAP')->execute();
```

> Info: Um índice de bitmap é uma especificação
tipo especial de índice de banco de dados que usa bitmaps ou matriz de bits. No índice de bitmap, o Oracle armazena um
> bitmap para cada chave de índice.
>
> Cada chave de índice armazena ponteiros para várias linhas. Por exemplo, se você criar um índice de bitmap na coluna gênero da tabela de membros.

## Restrições (Constraints)

### Adicionar restrição `UNIQUE`

Para adicionar uma restrição exclusiva a uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addUnique()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addUnique('{{%customer}}', 'uq-customer-name', 'name')->execute();
```

### Elimine a restrição `UNIQUE`

Para eliminar uma restrição exclusiva de uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropUnique()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropUnique('{{%customer}}', 'uq-customer-name')->execute();
```

### Adicionar uma restrição `CHECK`

Para adicionar uma restrição `CHECK` a uma tabela existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addCheck()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCheck('{{%customer}}', 'ck-customer-status', 'status > 0')->execute();
```

### Elimine a restrição `CHECK`

Para eliminar uma restrição `CHECK` existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropCheck()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCheck('{{%customer}}', 'ck-customer-status')->execute();
```

## Comentários

### Adicionar comentário a uma coluna

Para adicionar um comentário a uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addCommentOnColumn()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCommentOnColumn('{{%customer}}', 'name', 'This is a customer name')->execute();
```

### Adicionar comentário a uma tabela

Para adicionar um comentário a uma tabela existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::addCommentOnTable()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->addCommentOnTable('{{%customer}}', 'This is a customer table')->execute();
```

### Remover comentário de uma coluna

Para remover um comentário de uma coluna existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropCommentFromColumn()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCommentFromColumn('{{%customer}}', 'name')->execute();
```

### Eliminar comentário de uma tabela

Para eliminar um comentário de uma tabela existente, você pode usar o método `Yiisoft\Db\Command\CommandInterface::dropCommentFromTable()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->dropCommentFromTable('{{%customer}}')->execute();
```
