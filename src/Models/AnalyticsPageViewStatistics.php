<?php

namespace AndreasElia\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;

/**
 * Class AnalyticsPageViewStatistics
 *
 * This model represents the statistics for page views.
 */
class AnalyticsPageViewStatistics extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'time_window',
        'page',
        'page_views'
    ];


    public function scopeFilter($query, $period = 'today')
    {
        $today = CarbonImmutable::today($this->getTimezone())
            ->setTimezone(config('app.timezone'));

        if (! in_array($period, ['today', 'yesterday'])) {
            [$interval, $unit] = explode('_', $period);

            return $query->where('time_window', '>=', $today->sub($unit, $interval));
        }

        if ($period === 'yesterday') {
            return $query->whereBetween('time_window', [$today->subDay(), $today]);
        }

        return $query->where('time_window', '>=', $today);
    }

    public function scopeUri($query, $uri = null)
    {
        $query->when(
            $uri, 
            function ($query, string $uri) {
                $query->where('page', $uri);
            }
        );
    }

    public function getTimezone(): string
    {
        $timezone = null;

        if (isset(static::$timezoneResolver) && is_callable(static::$timezoneResolver)) {
            $timezone = call_user_func(static::$timezoneResolver);
        }

        return empty($timezone)
            ? config('app.timezone')
            : $timezone;
    }
}
