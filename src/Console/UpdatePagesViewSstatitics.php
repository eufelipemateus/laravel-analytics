<?php

namespace AndreasElia\Analytics\Console;

use Illuminate\Console\Command;
use  AndreasElia\Analytics\Jobs\UpdateAnalyticsPageViewStatisticsJob;


class UpdatePagesViewSstatitics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-pages-view-statitics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the pages view statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('analytics.analyticsGraph')) {
            UpdateAnalyticsPageViewStatisticsJob::dispatch()->onQueue('default');
        }

    }
}
