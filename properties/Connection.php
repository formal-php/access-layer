<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer;

use Formal\AccessLayer\{
    Connection as Concrete,
    Query\SQL,
    Query\DropTable,
    Table\Name,
};
use Innmind\BlackBox\{
    Set,
    Property,
};

final class Connection
{
    /**
     * @return Set<Property>
     */
    public static function any(): Set
    {
        return Set\Properties::any(...\array_map(
            static fn($class) => [$class, 'any'](),
            self::list(),
        ));
    }

    /**
     * @return list<class-string<Property>>
     */
    public static function list(): array
    {
        return [
            Connection\AllowToStartTwoQueriesInParallel::class,
            Connection\AnInvalidQueryMustThrow::class,
            Connection\AnInvalidLazyQueryMustThrow::class,
            Connection\AnInvalidLazySelectMustThrow::class,
            Connection\AQueryWithoutTheCorrectNumberOfParametersMustThrow::class,
            Connection\MustThrowWhenValueDoesntFitTheSchema::class,
            Connection\Insert::class,
            Connection\MultipleInsertsAtOnce::class,
            Connection\ParametersCanBeBoundByName::class,
            Connection\ParametersCanBeBoundByIndex::class,
            Connection\ContentInsertedAfterStartOfTransactionIsAccessible::class,
            Connection\ContentIsAccessibleAfterCommit::class,
            Connection\ContentIsNotAccessibleAfterRollback::class,
            Connection\CommittingAnUnstartedTransactionMustThrow::class,
            Connection\RollbackingAnUnstartedTransactionMustThrow::class,
            Connection\ParameterTypesCanBeSpecified::class,
            Connection\CreateTable::class,
            Connection\CreateTableWithPrimaryKey::class,
            Connection\CreateTableWithForeignKey::class,
            Connection\CreatingSameTableTwiceMustThrow::class,
            Connection\CreateTableIfNotExists::class,
            Connection\CanDropUnknownDatabaseIfNotExists::class,
            Connection\DroppingUnknownDatabaseMustThrow::class,
            Connection\SelectEverything::class,
            Connection\SelectColumns::class,
            Connection\SelectAliasedColumns::class,
            Connection\SelectWhere::class,
            Connection\SelectOffset::class,
            Connection\SelectLimit::class,
            Connection\SelectOrder::class,
            Connection\Update::class,
            Connection\UpdateSpecificRow::class,
            Connection\Delete::class,
            Connection\DeleteSpecificRow::class,
        ];
    }

    public static function seed(Concrete $connection): void
    {
        $connection(DropTable::ifExists(new Name('test')));
        $connection(SQL::of('CREATE TABLE `test` (`id` varchar(36) NOT NULL,`username` varchar(255) NOT NULL, `registerNumber` bigint NOT NULL, PRIMARY KEY (id));'));
    }
}
