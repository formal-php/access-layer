# Transactions

```php
use Formal\AccessLayer\Query\{
    StartTransaction,
    Commit,
    Rollback,
};

try {
    $connection(new StartTransaction);
    $connection(/* any insert, update or delete query */);
    $connection(new Commit);
} catch (\Throwable $e) {
    $connection(new Rollback);

    throw $e;
}
```
