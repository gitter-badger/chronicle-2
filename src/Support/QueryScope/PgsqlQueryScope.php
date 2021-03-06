<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\QueryScope;

use Illuminate\Database\Query\Builder;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;

class PgsqlQueryScope extends ConnectionQueryScope
{
    public function matchAggregateIdAndTypeGreaterThanVersion(string $aggregateId,
                                                              string $aggregateType,
                                                              int $aggregateVersion): QueryFilter
    {
        Assertion::greaterThan($aggregateVersion, 0, 'Aggregate version must be greater than 0');

        $callback = function (Builder $query) use ($aggregateId, $aggregateType, $aggregateVersion): void {
            $query
                ->whereJsonContains('headers->__aggregate_id', $aggregateId)
                ->whereJsonContains('headers->__aggregate_type', $aggregateType)
                ->whereRaw('CAST(headers->>\'__aggregate_version\' AS INT) > ' . $aggregateVersion)
                ->orderByRaw('CAST(headers->>\'__aggregate_version\' AS INT)');
        };

        return $this->wrap($callback);
    }
}
