<?php

namespace AndreasElia\Analytics\Models;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public const PERIODS = [
        'today',
        'yesterday',
        '1_week',
        '30_days',
        '6_months',
        '12_months',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_page_views';

    /** @var array */
    protected $fillable = [
        'session',
        'uri',
        'source',
        'country',
        'browser',
        'device',
        'host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    protected static Closure $timezoneResolver;

    public static function resolveTimezoneUsing(Closure $callback): void
    {
        static::$timezoneResolver = $callback;
    }

    public function setSourceAttribute($value): void
    {
        $this->attributes['source'] = $value
            ? preg_replace('/https?:\/\/(www\.)?([a-z\-\.]+)\/?.*/i', '$2', $value)
            : $value;
    }

    public function setCountryAttribute($value): void
    {
        if (class_exists('Locale')) {
            $this->attributes['country'] = \Locale::getDisplayRegion($value, 'en');
        } else {
            // Fallback: salva o valor original se Locale não estiver disponível
            $this->attributes['country'] = $value;
        }
    }

    public function getTypeAttribute($value): string
    {
        return ucfirst($value);
    }

    public function scopeFilter($query, $period = 'today')
    {
        if (! in_array($period, self::PERIODS, true)) {
            $period = 'today';
        }

        $today = CarbonImmutable::today($this->getTimezone())
            ->setTimezone(config('app.timezone'));

        if (! in_array($period, ['today', 'yesterday'], true)) {
            [$interval, $unit] = explode('_', $period, 2);

            return $query->where('created_at', '>=', $today->sub($unit, (int) $interval));
        }

        if ($period === 'yesterday') {
            return $query->whereBetween('created_at', [$today->subDay(), $today]);
        }

        return $query->where('created_at', '>=', $today);
    }

    public function scopeUri($query, $uri = null)
    {
        $query->when($uri, function ($query, string $uri) {
            $query->where('uri', $uri);
        });
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
