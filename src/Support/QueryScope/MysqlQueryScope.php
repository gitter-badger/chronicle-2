<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\QueryScope;

use Illuminate\Database\Query\Builder;
use Plexikon\Chronicle\Exception\Assertion;

class MysqlQueryScope extends ConnectionQueryScope
{
    public function matchAggregateIdAndTypeGreaterThanVersion(string $aggregateId, string $aggregateType, int $aggregateVersion): callable
    {
        Assertion::greaterThan($aggregateVersion, 0, 'Aggregate version must be greater than 0');

        return function (Builder $query) use ($aggregateId, $aggregateType, $aggregateVersion): void {
            $query
                ->where('aggregate_id', $aggregateId)
                ->where('aggregate_type', $aggregateType)
                ->where('aggregate_version', '>', $aggregateVersion)
                ->orderby('aggregate_version');
        };
    }
}
