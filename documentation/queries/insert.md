# `Insert`

```php
use Formal\AccessLayer\{
    Query\Insert,
    Query\Parameter,
    Table\Name,
    Row,
};

$insert = Insert::into(
    new Name('users'),
    Row::of([
        'username' => 'some username',
        'name' => 'some name',
    ]),
    // you can add as many rows as you wish
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
    new Name('users'),
    new Row(
        new Row\Value(new Column\Name('username'), 'some username', Type::string),
        new Row\Value(new Column\Name('name'), 'some name', Type::string),
    ),
);
$connection($insert);
```
