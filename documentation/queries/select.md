# `Select`

```php
use Formal\AccesLayer\{
    Query\Select,
    Table\Name,
};

$select = Select::from(new Name('users'));
$users = $connection($select);
```

This will return all the content of the `users` table.

!!! note ""
    If you replace the constructor `::from()` by `::onDemand()` it will run your query lazily by returning a lazy `Sequence`, meaning it won't keep the results in memory allowing you to handle very large results.

## Specifying columns to return

```php
use Formal\AccesLayer\{
    Query\Select,
    Table\Name,
    Table\Column,
};

$select = Select::from(new Name('users'))->columns(
    new Column\Name('username'),
    new Column\Name('name'),
);
$users = $connection($select);
```

## Filter rows

To filter the rows to select this library uses the [specification pattern](https://github.com/innmind/specification).

```php
use Formal\AccesLayer\{
    Query\Select,
    Table\Name,
    Table\Column,
};
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};

final class Username
{
    public static function of(string $username): Property
    {
        return Property::of(
            'username', // this is the name of the column,
            Sign::equality,
            $username,
        );
    }
}

$select = Select::from(new Name('users'))->where(
    Username::of('some username')->or(Username::of('other username')),
);
$users = $connection($select);
```

!!! note ""
    The property name can include the name of the table to match by using the format `'{table}.{column}'`.

    The value of the specification can also be a query (this will translated to a sub query).
