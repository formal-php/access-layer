# `Update`

```php
use Formal\AccessLayer\{
    Query\Update,
    Table\Name,
    Row,
};

$update = Update::set(
    Name::of('users'),
    Row::of([
        'name' => 'some value',
    ]),
);
$connection($update);
```

This example will set the `name` to `some value` for all the users of the table.

### Specify rows to update

To filter the rows to update this library uses the [specification pattern](https://github.com/innmind/specification).

```php
use Formal\AccesLayer\{
    Query\Update,
    Table\Name,
    Row,
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

$update = Update::set(
    Name::of('users'),
    Row::of([
        'name' => 'some value',
    ]),
);
$update = $update->where(
    Username::of('some username')->or(Username::of('other username')),
);
$connection($update);
```

!!! note ""
    The property name can include the name of the table to match by using the format `'{table}.{column}'`.
