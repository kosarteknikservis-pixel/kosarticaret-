<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsIdentity
{
    /**
     * @param  Builder<\App\Models\AnalyticsEvent>  $query
     */
    public static function countDistinct(Builder $query, bool $excludeStaff = true): int
    {
        $expression = self::identityExpression();

        $q = (clone $query)
            ->leftJoin('analytics_visitors as analytics_identity_visitors', 'analytics_identity_visitors.id', '=', 'analytics_events.visitor_id');

        if ($excludeStaff) {
            $q = self::excludeStaff($q);
        }

        return (int) $q
            ->selectRaw("COUNT(DISTINCT {$expression}) as aggregate")
            ->value('aggregate');
    }

    /**
     * @param  Builder<\App\Models\AnalyticsEvent>  $query
     * @return Builder<\App\Models\AnalyticsEvent>
     */
    public static function excludeStaff(Builder $query): Builder
    {
        $adminIds = User::query()->where('is_admin', true)->pluck('id');

        if ($adminIds->isEmpty()) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($adminIds) {
            $inner->where(function (Builder $eventUser) use ($adminIds) {
                $eventUser->whereNull('analytics_events.user_id')
                    ->orWhereNotIn('analytics_events.user_id', $adminIds);
            })->where(function (Builder $visitorUser) use ($adminIds) {
                $visitorUser->whereNull('analytics_identity_visitors.user_id')
                    ->orWhereNotIn('analytics_identity_visitors.user_id', $adminIds);
            });
        });
    }

    /**
     * Staff olmayan müşteri event sorgusu (join yoksa visitor üzerinden filtreler).
     *
     * @param  Builder<\App\Models\AnalyticsEvent>  $query
     * @return Builder<\App\Models\AnalyticsEvent>
     */
    public static function customerEvents(Builder $query): Builder
    {
        $adminIds = User::query()->where('is_admin', true)->pluck('id');

        if ($adminIds->isEmpty()) {
            return $query;
        }

        return $query
            ->where(function (Builder $q) use ($adminIds) {
                $q->whereNull('analytics_events.user_id')
                    ->orWhereNotIn('analytics_events.user_id', $adminIds);
            })
            ->where(function (Builder $q) use ($adminIds) {
                $q->whereNull('analytics_events.visitor_id')
                    ->orWhereNotExists(function ($sub) use ($adminIds) {
                        $sub->select(DB::raw(1))
                            ->from('analytics_visitors')
                            ->whereColumn('analytics_visitors.id', 'analytics_events.visitor_id')
                            ->whereIn('analytics_visitors.user_id', $adminIds);
                    });
            });
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
