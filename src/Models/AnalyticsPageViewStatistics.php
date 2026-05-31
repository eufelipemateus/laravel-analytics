<?php

namespace AndreasElia\Analytics\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

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
        'page_views',
    ];

    public $timestamps = false;

    protected $casts = [
        'time_window' => 'datetime',
    ];

    public function scopeFilter($query, $period = 'today')
    {
        if (! in_array($period, PageView::PERIODS, true)) {
            $period = 'today';
        }

        $today = CarbonImmutable::today($this->getTimezone())
            ->setTimezone(config('app.timezone'));

        if (! in_array($period, ['today', 'yesterday'], true)) {
            [$interval, $unit] = explode('_', $period, 2);

            return $query->where('time_window', '>=', $today->sub($unit, (int) $interval));
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
        return (new PageView())->getTimezone();
    }
}
