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
    public static function properties(): Set
    {
        return Set\Properties::any(...self::list());
    }

    /**
     * @return list<Set<Property>>
     */
    public static function list(): array
    {
        return [
            Connection\AllowToStartTwoQueriesInParallel::any(),
            Connection\AnInvalidQueryMustThrow::any(),
            Connection\AQueryWithoutTheCorrectNumberOfParametersMustThrow::any(),
            Connection\MustThrowWhenValueDoesntFitTheSchema::any(),
            Connection\Insert::any(),
            Connection\MultipleInsertsAtOnce::any(),
            Connection\ParametersCanBeBoundByName::any(),
            Connection\ParametersCanBeBoundByIndex::any(),
            Connection\ContentInsertedAfterStartOfTransactionIsAccessible::any(),
            Connection\ContentIsAccessibleAfterCommit::any(),
            Connection\ContentIsNotAccessibleAfterRollback::any(),
            Connection\CommittingAnUnstartedTransactionMustThrow::any(),
            Connection\RollbackingAnUnstartedTransactionMustThrow::any(),
            Connection\ParameterTypesCanBeSpecified::any(),
            Connection\CreateTable::any(),
            Connection\CreatingSameTableTwiceMustThrow::any(),
            Connection\CreateTableIfNotExists::any(),
            Connection\CanDropUnknownDatabaseIfNotExists::any(),
            Connection\DroppingUnknownDatabaseMustThrow::any(),
            Connection\SelectEverything::any(),
            Connection\SelectColumns::any(),
            Connection\SelectWhere::any(),
            Connection\Update::any(),
            Connection\UpdateSpecificRow::any(),
            Connection\Delete::any(),
            Connection\DeleteSpecificRow::any(),
        ];
    }

    public static function seed(Concrete $connection): void
    {
        $connection(DropTable::ifExists(new Name('test')));
        $connection(new SQL('CREATE TABLE `test` (`id` varchar(36) NOT NULL,`username` varchar(255) NOT NULL, `registerNumber` bigint NOT NULL, PRIMARY KEY (id));'));
    }
}
