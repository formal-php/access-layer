# `Logger`

This connection sits on top of another and will log all queries that will be executed and record any failure that may occur.

```php
use Formal\AccessLayer\{
    Connection\Logger,
    Connection,
};
use Psr\Log\LoggerInterface;

$connection = Logger::psr(
    /* any instance of Connection */,
    /* any instance of LoggerInterface */,
);
```

!!! note ""
    It doesn't log any information about the returned rows to prevent _unwrapping_ the deferred `Sequence` returned by [`PDO`](pdo.md).

!!! warning ""
    It won't log any errors for lazy queries since the query is not executed until the first call on the sequence.
