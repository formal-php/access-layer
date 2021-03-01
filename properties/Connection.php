<?php
declare(strict_types = 1);

namespace Properties\Formal\AccessLayer;

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
        ];
    }
}
