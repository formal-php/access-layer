# `Insert`

```php
use Formal\AccessLayer\{
    Query\Insert,
    Query\Parameter,
    Table\Name,
    Row,
};

$insert = Insert::into(
    Name::of('users'),
    Row::of([
        'username' => 'some username',
        'name' => 'some name',
    ]),
);
$connection($insert);
```

If you need to specify the type of the values you want to insert you can do like this:

```php
use Formal\AccessLayer\{
    Query\Insert,
    Query\Parameter,
    Query\Parameter\Type,
    Table\Name,
    Table\Column,
    Row,
};

$insert = Insert::into(
    Name::of('users'),
    Row::new(
        Row\Value::of(
            Column\Name::of('username'),
            'some username',
            Type::string,
        ),
        Row\Value::of(
            Column\Name::of('name'),
            'some name',
            Type::string,
        ),
    ),
);
$connection($insert);
```

## Multiple inserts

`Insert` allows you to insert a single row at a time. This forces you to run multiple queries which can be slow, but allows you to specify different columns for each row inserted.

Instead you can use `MultipleInsert`:

```php
use Formal\AccessLayer\{
    Query\MultipleInsert,
    Table\Name,
    Table\Column,
    Row,
};
use Innmind\Immutable\Sequence;

$insert = MultipleInsert::into(
    Name::of('users'),
    Column\Name::of('username'),
    Column\Name::of('name'),
);
$connection($insert(Sequence::of(
    Row::of([
        'username' => 'john',
        'name' => 'John Doe',
    ]),
    Row::of([
        'username' => 'jane',
        'name' => 'Jane Doe',
    ]),
)));
```
!!! warning ""
    Each `Row` must specify the same amount of columns and in the same order, otherwise it will fail.
