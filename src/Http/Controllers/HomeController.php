<?php

namespace AndreasElia\Analytics\Http\Controllers;

use AndreasElia\Analytics\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use AndreasElia\Analytics\Models\AnalyticsPageViewStatistics;

class HomeController extends Controller
{
    protected array $scopes = [];

    public function index(Request $request): View
    {
        $period = $request->input('period', 'today');
        $uri = $request->input('uri');

        $this->scopes = [
            'filter' => [$period],
            'uri' => [$uri],
        ];

        return view('analytics::dashboard', [
            'period' => $period,
            'uri' => $uri,
            'periods' => $this->periods(),
            'stats' => $this->stats(),
            'pages' => $this->pages(),
            'sources' => $this->sources(),
            'users' => $this->users(),
            'devices' => $this->devices(),
            'utm' => $this->utm(),
            'graph' => $this->graph(),
        ]);
    }

    protected function periods(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '1_week' => 'Last 7 days',
            '30_days' => 'Last 30 days',
            '6_months' => 'Last 6 months',
            '12_months' => 'Last 12 months',
        ];
    }

    protected function stats(): array
    {
        return [
            [
                'key' => 'Last 10 minutes',
                'value' => PageView::query()
                    ->where('created_at', '>=', now()->subMinutes(10))
                    ->groupBy('session')
                    ->pluck('session')
                    ->count(),
            ],
            [
                'key' => 'Last 1 hour',
                'value' => PageView::query()
                    ->where('created_at', '>=', now()->subHour())
                    ->groupBy('session')
                    ->pluck('session')
                    ->count(),
            ],
            [
                'key' => 'Unique Users',
                'value' => PageView::query()
                    ->scopes($this->scopes)
                    ->groupBy('session')
                    ->pluck('session')
                    ->count(),
            ],
            [
                'key' => 'Page Views',
                'value' => PageView::query()
                    ->scopes($this->scopes)
                    ->count(),
            ],
        ];
    }

    protected function pages(): Collection
    {
        return PageView::query()
            ->scopes($this->scopes)
            ->select('uri as page', DB::raw('count(*) as users'))
            ->groupBy('page')
            ->orderBy('users', 'desc')
            ->get();
    }

    protected function sources(): Collection
    {
        if (in_array('source', config('analytics.ignoredColumns', []))) {
            return collect();
        }

        return PageView::query()
            ->scopes($this->scopes)
            ->select('source as page', DB::raw('count(*) as users'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderBy('users', 'desc')
            ->get();
    }

    protected function users(): Collection
    {
        return PageView::query()
            ->scopes($this->scopes)
            ->select('country', DB::raw('count(*) as users'))
            ->groupBy('country')
            ->orderBy('users', 'desc')
            ->get();
    }

    protected function devices(): Collection
    {
        if (in_array('device', config('analytics.ignoredColumns', []))) {
            return collect();
        }

        return PageView::query()
            ->scopes($this->scopes)
            ->select('device as type', DB::raw('count(*) as users'))
            ->groupBy('type')
            ->orderBy('users', 'desc')
            ->get();
    }

    protected function utm(): Collection
    {
        $utm = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
        ];

        return collect($utm)
            ->filter(fn (string $column) => ! in_array($column, config('analytics.ignoredColumns', [])))
            ->mapWithKeys(fn (string $key) => [$key => [
                'key' => $key,
                'items' => PageView::query()
                    ->select([$key, DB::raw('count(*) as count')])
                    ->scopes($this->scopes)
                    ->whereNotNull($key)
                    ->groupBy($key)
                    ->orderBy('count', 'desc')
                    ->get()
                    ->map(fn ($item) => [
                        'value' => $item->{$key},
                        'count' => $item->count,
                    ]),
            ]])
            ->filter(fn (array $set) => $set['items']->count() > 0);
    }

    protected function graph(): object
    {
        $stats = AnalyticsPageViewStatistics::query()
            ->select('time_window', 'page', DB::raw('SUM(page_views) as total_views'))
            ->scopes($this->scopes)
            ->groupBy('time_window', 'page')
            ->orderBy('total_views', 'desc')
            ->get();
        
        $groupedData = $stats->groupBy('page');

        $chartData = [
            'labels' => $stats->pluck('time_window')->unique()->sort()->values()->map(fn($t) => date('H:i', strtotime($t))),
            'datasets' => $groupedData->take(10)->map(
                function ($group, $page) {
                    return [
                        'label' => $page,
                        'data' => $group->pluck('total_views'),
                        'borderColor' => '#' . substr(md5($page), 0, 6),
                        'fill' => false,
                    ];
                }
            )->values()
        ];

        return (object) $chartData;
    }
}
