# `Select`

```php
use Formal\AccesLayer\{
    Query\Select,
    Table\Name,
};

$select = new Select(new Name('users'));
$users = $connection($select);
```

This will return all the content of the `users` table.

## Specifying columns to return

```php
use Formal\AccesLayer\{
    Query\Select,
    Table\Name,
    Table\Column,
};

$select = new Select(new Name('users'));
$select = $select->columns(new Column\Name('username'), new Column\Name('name'));
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

$select = new Select(new Name('users'));
$select = $select->where(
    (new Username('some username'))->or(new Username('other username')),
);
$users = $connection($select);
```

**Note**: the property name can include the name of the table to match by using the format `'{table}.{column}'`.
