# access-layer

[![Build Status](https://github.com/formal-php/access-layer/workflows/CI/badge.svg?branch=master)](https://github.com/formal-php/access-layer/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/formal-php/access-layer/branch/develop/graph/badge.svg)](https://codecov.io/gh/formal-php/access-layer)
[![Type Coverage](https://shepherd.dev/github/formal-php/access-layer/coverage.svg)](https://shepherd.dev/github/formal-php/access-layer)

This a simple abstraction layer on top of builtin `\PDO` class to offer a minimalist api.

The goal is separate expression of queries and their execution by using immutable structures and eliminating states wherever possible.

> [!IMPORTANT]
> you must use [`vimeo/psalm`](https://packagist.org/packages/vimeo/psalm) to make sure you use this library correctly.

## Installation

```sh
composer require formal/access-layer
```

## Example

```php
use Formal\AccessLayer\{
    Connection\Lazy,
    Connection\PDO,
    Query\SQL,
    Row,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

$connection = Lazy::of(static fn() => PDO::of(Url::of('mysql://user:pwd@127.0.0.1:3306/database_name')));

$rows = $connection(SQL::of('SELECT * FROM `some_table`'));
$rows; // instanceof Sequence<Row>
```

## Documentation

Complete documentation can be found at <http://formal-php.github.io/access-layer/>.
