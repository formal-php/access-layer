# Changelog

## [Unreleased]

### Added

- `Formal\AccessLayer\Query\Parameter\Type::for()` to determine a type for any given value

## 2.15.0 - 2024-02-10

### Added

- `Formal\AccessLayer\Query\CreateTable::unique()`
- `Formal\AccessLayer\Query\Constraint\Unique`

## 2.14.0 - 2024-02-10

### Added

- `Formal\AccessLayer\Query\Constraint\ForeignKey::named()`

## 2.13.0 - 2024-01-28

### Added

- Queries can be used a value of a specification in a where clause

## 2.12.0 - 2023-09-23

### Added

- Support for `innmind/immutable:~5.0`

## 2.11.0 - 2023-08-13

### Added

- `Formal\AccessLayer\Row\Value` now accepts a namespaced column name
- `Formal\AccessLayer\Row\Value::columnSql()`

## 2.10.0 - 2023-08-12

### Changed

- `Formal\AccessLayer\Query\Delete` deletes from the main table instead of all joined tables as well

## 2.9.0 - 2023-08-12

### Added

- `Formal\AccessLayer\Query\Constraint\ForeignKey::onDeleteSetNull()`

## 2.8.0 - 2023-08-12

### Added

- You can now use an aliased table name to update from with `Formal\AccessLayer\Update`
- `Formal\AccessLayer\Update::join()`

## 2.7.1 - 2023-08-12

### Fixed

- The logic between `Sign::startsWith` and `Sign::endsWith` were inversed
- `Sign::contains`, `Sign::startsWith` and `Sign::endsWith` would not yield the expected result when containing special characters `\`, `_` and `%` (as they're special pattern characters), these characters are now escaped so it would exactly match

## 2.7.0 - 2023-08-12

### Added

- You can now use an aliased table name to delete from with `Formal\AccessLayer\Delete`

## 2.6.0 - 2023-08-06

### Added

- `Formal\AccessLayer\Query\Constraint\PrimaryKey`
- `Formal\AccessLayer\Query\Constraint\ForeignKey`
- `Formal\AccessLayer\Query\CreateTable::constraint()`
- `Formal\AccessLayer\Query\Delete::join()`

### Fixed

- Using a namespaced column as a property of a specification failed when using `Sign::in`

## 2.5.0 - 2023-08-06

### Added

- `Formal\AccessLayer\Query\Select::count()`

### Fixed

- Using a number as a column alias crashed because it wasn't a string

## 2.4.0 - 2023-08-05

### Added

- `Formal\AccessLayer\Query\Select::join()`
- `Formal\AccessLayer\Query\Select\Join`

## 2.3.0 - 2023-08-05

### Added

- `Formal\AccessLayer\Query\Select::limit()`
- `Formal\AccessLayer\Query\Select::orderBy()`
- `Formal\AccessLayer\Query\Select\Direction`

## 2.2.0 - 2023-07-30

### Added

- `Formal\AccessLayer\Table\Name::of()`
- `Formal\AccessLayer\Table\Name::as()`
- `Formal\AccessLayer\Table\Column::of()`
- `Formal\AccessLayer\Table\Column\Name::of()`
- `Formal\AccessLayer\Table\Column\Name::in()`
- `Formal\AccessLayer\Table\Column\Name::as()`
- `Formal\AccessLayer\Table\Name\Aliased`
- `Formal\AccessLayer\Table\Column\Name\Namespaced`
- `Formal\AccessLayer\Table\Column\Name\Aliased`

### Changed

- Require `innmind/black-box` `5`

### Removed

- Support for PHP `8.1`

## 2.1.0 - 2023-07-07

### Added

- `Formal\AccessLayer\Row::toArray()`
- Allow to specify the connection charset via the `charset` query parameter in the connection `Url`
