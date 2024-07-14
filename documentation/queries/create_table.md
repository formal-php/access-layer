# `CreateTable`

```php
use Formal\AccessLayer\{
    Query\CreateTable,
    Table\Name,
    Table\Column,
};

$create = CreateTable::named(
    Name::of('users'),
    Column::of(
        Column\Name::of('username'),
        Column\Type::varchar(),
    ),
    Column::of(
        Column\Name::of('name'),
        Column\Type::varchar(),
    ),
);
$connection($create);
```

This query will fail if the table does exist, you can prevent this like so:

```php
use Formal\AccessLayer\{
    Query\CreateTable,
    Table\Name,
    Table\Column,
};

$create = CreateTable::ifNotExists(
    Name::of('users'),
    Column::of(
        Column\Name::of('username'),
        Column\Type::varchar(),
    ),
    Column::of(
        Column\Name::of('name'),
        Column\Type::varchar(),
    ),
);
$connection($create);
```

## Constraints

### Primary key

You can specify the primary key of the table like so:

```php
$create = CreateTable::named(
    Name::of('users'),
    Column::of(
        Column\Name::of('id'),
        Column\Type::int(),
    ),
);
$create = $create->primaryKey(Column\Name::of('id'));
$connection($create);
```

### Foreign key

```php
$create = CreateTable::named(
    Name::of('address'),
    Column::of(
        Column\Name::of('user'),
        Column\Type::int(),
    ),
    Column::of(
        Column\Name::of('address'),
        Column\Type::text(),
    ),
);
$create = $create->foreignKey(
    Column\Name::of('user'),
    Name::of('users'),
    Column\Name::of('id'),
);
$connection($create);
```

!!! note ""
    This will name the foreign key `FK_user_id` so it's easier to reference it afterwards.
