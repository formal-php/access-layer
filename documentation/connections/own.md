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

Below is an example of a PHPUnit test case that you can extend to add your specific test cases:

```php
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Properties\Formal\AccessLayer\Connection;

class SentryTest extends TestCase
{
    use BlackBox;

    public function setUp(): void
    {
        Connection::seed($this->connection());
    }

    // you can add here test cases like in any other PHPUnit class

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(function($property) {
                $connection = $this->connection();

                if (!$property->applicableTo($connection)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($connection);
            });
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(Connection::properties())
            ->disableShrinking()
            ->then(function($properties) {
                $properties->ensureHeldBy($this->connection());
            });
    }

    public function properties(): iterable
    {
        foreach (Connection::list() as $property) {
            yield [$property];
        }
    }

    private function connection(): PDO
    {
        return new Sentry(/* add the arguments of your implementation here */);
    }
}
```

This will ensure that your implementation hold all the properties that must be held by all the connection implementations so you can swap the implementations without side effects.
