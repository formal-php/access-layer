# `Update`

```php
use Formal\AccessLayer\{
    Query\Update,
    Table\Name,
    Row,
};

$update = new Update(
    new Name('users'),
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
        return Sign::equality();
    }

    public function value(): mixed
    {
        return $this->value;
    }
}

$update = new Update(
    new Name('users'),
    Row::of([
        'name' => 'some value',
    ]),
);
$update = $update->where(
    (new Username('some username'))->or(new Username('other username')),
);
$connection($update);
```

**Note**: the property name can include the name of the table to match by using the format `'{table}.{column}'`.
