# `SQL`

This is the most basic way to execute a query through a connection.

```php
use Formal\AccessLayer\{
    Query\SQL,
    Row,
};

$query = new SQL('SHOW TABLES');
$tables = $connection($query);
$tables->foreach(function(Row $row): void {
    echo $row->column('Tables_in_database_name');
});
```

## Parameters

For some queries you will need to specify parameters to provide values, you can bind them either by specifying their name or by an index

**Important**: do not copy the values directly in the sql query as you'll be vulnerable to sql injection.

### Bound by name

This useful when you use the same parameter multiple times in your query.

```php
use Formal\AccessLayer\Query\Parameter;

$insert = new SQL('INSERT INTO `users` (`username`, `name`) VALUES (:username, :username)');
$insert = $insert->with(Parameter::named('username', 'some username value'));
$connection($insert);
```

### Bound by index

This is the most simple approach as you only have to worry that you add the parameters in the order specified in the sql query.

```php
use Formal\AccessLayer\Query\Parameter;

$insert = new SQL('INSERT INTO `users` (`username`, `name`) VALUES (?, ?)');
$insert = $insert
    ->with(Parameter::of('some username value'))
    ->with(Parameter::of('some name'));
$connection($insert);
```

**Note**: traditionally the index value rely on the user (you) to be specified (see [`PDOStatement::bindValue`](https://www.php.net/manual/en/pdostatement.bindvalue.php)), but this increase the probability for you to make an error. This problem is resolved here as the order in which the parameters are provided is always respected, this allows the connection to correctly provide the index to `\PDO`.
