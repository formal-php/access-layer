# `Logger`

This connection sits on top of another and will log all queries that will be executed and record any failure that maay occur.

```php
use Formal\AccessLayer\{
    Connection\Logger,
    Connection,
};
use Psr\Log\LoggerInterface;

$connection = new Logger(
    /* any instance of Connection */,
    /* any instance of LoggerInterface */,
);
```

**Note**: it doesn't log any information about the returned rows to prevent _unwrapping_ the deferred `Sequence` returned by [`PDO`](pdo.md).
