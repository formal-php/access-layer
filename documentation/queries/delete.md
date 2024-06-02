# `Delete`

```php
use Formal\AccessLayer\{
    Query\Delete,
    Table\Name,
};

$delete = Delete::from(new Name('users'));
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
    Comparator,
    Composable,
    Sign,
};

final class Username implements Comparator
{
    use Composable;

    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function property(): string
    {
        return 'username'; // this is the name of the column
    }

    public function sign(): Sign
    {
        return Sign::equality;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}

$delete = Delete::from(new Name('users'))->where(
    (new Username('some username'))->or(new Username('other username')),
);
$connection($delete);
```

!!! note ""
    The property name can include the name of the table to match by using the format `'{table}.{column}'`.
