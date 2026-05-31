<?php

namespace AndreasElia\Analytics\Jobs;

use AndreasElia\Analytics\Models\PageView;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateAnalyticsPageViewStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $endTime = CarbonImmutable::now()->startOfMinute();
        $endTime = $endTime->subMinutes($endTime->minute % 10);
        $startTime = $endTime->subMinutes(10);

        $pageViews = PageView::query()
            ->select('uri', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startTime)
            ->where('created_at', '<', $endTime)
            ->whereNotNull('uri')
            ->groupBy('uri')
            ->orderBy('count', 'desc')
            ->get();

        $data = $pageViews
            ->map(
                fn (PageView $pageView): array => [
                    'time_window' => $endTime,
                    'page' => $pageView->uri,
                    'page_views' => (int) $pageView->count,
                ]
            )
            ->all();

        DB::transaction(function () use ($data, $endTime): void {
            DB::table('analytics_page_view_statistics')
                ->where('time_window', $endTime)
                ->delete();

            if ($data !== []) {
                DB::table('analytics_page_view_statistics')->insert($data);
            }
        });
    }
}
