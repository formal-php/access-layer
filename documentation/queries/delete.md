# `Delete`

```php
use Formal\AccessLayer\{
    Query\Delete,
    Table\Name,
};

$delete = Delete::from(Name::of('users'));
$connection($delete);
```

This example will all the users of the table.

### Specify rows to delete

To filter the rows to delete this library uses the [specification pattern](https://github.com/innmind/specification).

```php
use Formal\AccesLayer\{
    Query\Delete,
    Table\Name,
};
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};

final class Username
{
    public static function of(string $value): Property
    {
        return Property::of(
            'username', // this is the name of the column
            Sign::equality,
            $value,
        );
    }
}

$delete = Delete::from(Name::of('users'))->where(
    Username::of('some username')->or(Username::of('other username')),
);
$connection($delete);
```

!!! note ""
    The property name can include the name of the table to match by using the format `'{table}.{column}'`.
