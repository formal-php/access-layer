# `PDO`

This is the basic connection from this library which is a simple abstraction on top of PHP builtin `\PDO` class.

To build an instance of it you only need the dsn to your database:

```php
use Formal\AccessLayer\{
    Connection\PDO,
    Query\SQL,
    Row,
};
use Innmind\Url\Url;

$connection = new PDO(Url::of('mysql://user:pwd@127.0.0.1:3306/database_name'));
```

When executing a [query](../queries/sql.md) through this connection it will return a [deferred `Sequence`](https://innmind.github.io/Immutable/SEQUENCE.html#defer) of rows. This means that the rows returned from the database are only loaded once you iterate over the sequence.

**Important**: as soon as you instanciate the class it will open a connection to the database, if you want to open it upon first query take a look at the [`Lazy` connection](lazy.md).
