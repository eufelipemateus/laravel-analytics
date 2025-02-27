<?php

namespace AndreasElia\Analytics\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use AndreasElia\Analytics\Models\PageView;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAnalyticsPageViewStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function handle()
    {
        $startTime = now()->subMinutes(10);
        $endTime = now();

        /*  $totalViews = DB::table('page_views')
            ->whereBetween('created_at', [$startTime, $endTime])
            ->count();*/

        $pageViews = PageView::query()
            ->select('uri', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startTime,$endTime])
            ->whereNotNull('uri') 
            ->groupBy('uri')
            ->orderBy('count', 'desc')
            ->get();
    
        $processPageViews = function ($pageViews) use ($endTime) {
            $data = [];
            foreach ($pageViews as $pageView) {
                $data[] = [
                    'time_window' => $endTime,
                    'page' => $pageView->uri,
                    'page_views' => $pageView->count,
                ];
            };

            DB::table('analytics_page_view_statistics')->insert($data);
        };

        $processPageViews($pageViews);              
    }
}
