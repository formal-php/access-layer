# Create your own

You can easily create your own connection to extend the behaviour of this library.

For example you could implement a connection that will send any failure to sentry like so:

```php
use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Immutable\Sequence;
use Sentry\ClientInterface;

final class Sentry implements Connection
{
    private Connection $connection;
    private ClientInterface $sentry;

    public function __construct(Connection $connection, ClientInterface $sentry)
    {
        $this->connection = $connection;
        $this->sentry = $sentry;
    }

    public function __invoke(Query $query): Sequence
    {
        try {
            return ($this->connection)($query);
        } catch (\Throwable $e) {
            $this->sentry->captureException($e);

            throw $e;
        }
    }
}
```

## Testing your connection

An important part of extending the behaviour of the connection with your own logic is to not change the current behaviour that other code may rely upon. This library helps you make sure you don't break these behaviours by providing you properties.

Below is an example of running properties via [BlackBox](https://innmind.github.io/BlackBox/):

```php
use Innmind\BlackBox\Set;
use Properties\Formal\AccessLayer\Connection as Properties;

$sentry = new Sentry(/* add the arguments of your implementation here */);
$connection = Set\Call::of(static function() use ($sentry) {
    Properties::seed($sentry);

    return $sentry;
});

yield properties(
    'Sentry properties',
    Properties::any(),
    $connection,
);

foreach (Properties::list() as $property) {
    yield property(
        $property,
        $connection,
    )->named('Sentry');
}
```

This will ensure that your implementation hold all the properties that must be held by all the connection implementations so you can swap the implementations without side effects.
