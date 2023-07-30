# Changelog

## [Unreleased]

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
