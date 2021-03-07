# `Lazy`

This is an abstraction on top of the [`PDO` connection](pdo.md) that will establish a connection to the database upon executing the first query.

This is useful because you can create a database connection object at the start of your script and it will never connect to the database if you never use the connection.

```php
use Formal\AccessLayer\{
    Connection\Lazy,
    Query\SQL,
    Row,
};
use Innmind\Url\Url;

$connection = new Lazy(Url::of('mysql://user:pwd@127.0.0.1:3306/database_name'));
```

**Note**: here we only replaced `PDO` by `Lazy`.
