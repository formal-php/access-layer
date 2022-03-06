# `DropTable`

```php
use Formal\AccessLayer\{
    Query\DropTable,
    Table\Name,
};

$drop = DropTable::named(new Name('users'));
$connection($drop);
```

This query will fail if the table doesn't exist, you can prevent this like so:

```php
use Formal\AccessLayer\{
    Query\DropTable,
    Table\Name,
};

$drop = DropTable::ifExists(new Name('users'));
$connection($drop);
```
