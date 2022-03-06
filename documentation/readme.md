# Getting started

This library is designed to eliminate state wherever possible when dealing with a database connection.

The result is an api can consist of only one method on the connection (`__invoke`) and one kind of argument (`Query`). Both can easily be extended through composition.

**Important**: you must use [`vimeo/psalm`](https://packagist.org/packages/vimeo/psalm) to make sure you use this library correctly.

## Installation

```sh
composer require formal/access-layer
```

## Basic usage

```php
use Formal\AccessLayer\{
    Connection\Lazy,
    Connection\PDO,
    Query\SQL,
    Row,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

$connection = new Lazy(static fn() => PDO::of(Url::of('mysql://user:pwd@127.0.0.1:3306/database_name')));

$rows = $connection(SQL::of('SELECT * FROM `some_table`'));
$rows; // instanceof Sequence<Row>
```
