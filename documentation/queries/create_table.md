# `CreateTable`

```php
use Formal\AccessLayer\{
    Query\CreateTable,
    Table\Name,
    Table\Column,
};

$create = new CreateTable(
    new Name('users'),
    new Column(
        new Column\Name('username'),
        Column\Type::varchar(),
    ),
    new Column(
        new Column\Name('name'),
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
    new Name('users'),
    new Column(
        new Column\Name('username'),
        Column\Type::varchar(),
    ),
    new Column(
        new Column\Name('name'),
        Column\Type::varchar(),
    ),
);
$connection($create);
```

## Constraints

### Primary key

You can specify the primary key of the table like so:

```php
$create = new CreateTable(
    new Name('users'),
    new Column(
        new Column\Name('id'),
        Column\Type::int(),
    ),
);
$create = $create->primaryKey(new Column\Name('id'));
$connection($create);
```

### Foreign key

```php
$create = new CreateTable(
    new Name('address'),
    new Column(
        new Column\Name('user'),
        Column\Type::int(),
    ),
    new Column(
        new Column\Name('address'),
        Column\Type::text(),
    ),
);
$create = $create->foreignKey(
    new Column\Name('user'),
    new Name('users'),
    new Column\Name('id'),
);
$connection($create);
```

**Note**: this will name the foreign key `FK_user_id` so it's easier to reference it afterwards.
