<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsIdentity
{
    /**
     * @param  Builder<\App\Models\AnalyticsEvent>  $query
     */
    public static function countDistinct(Builder $query): int
    {
        $expression = self::identityExpression();

        return (int) (clone $query)
            ->leftJoin('analytics_visitors as analytics_identity_visitors', 'analytics_identity_visitors.id', '=', 'analytics_events.visitor_id')
            ->selectRaw("COUNT(DISTINCT {$expression}) as aggregate")
            ->value('aggregate');
    }

    public static function identityExpression(): string
    {
        $userId = 'COALESCE(analytics_events.user_id, analytics_identity_visitors.user_id)';

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CASE WHEN {$userId} IS NOT NULL THEN ('u' || {$userId}) ELSE ('v' || analytics_events.visitor_id) END";
        }

        return "CASE WHEN {$userId} IS NOT NULL THEN CONCAT('u', {$userId}) ELSE CONCAT('v', analytics_events.visitor_id) END";
    }
}
