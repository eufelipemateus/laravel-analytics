<?php

namespace AndreasElia\Analytics\Console;

use AndreasElia\Analytics\Jobs\UpdateAnalyticsPageViewStatisticsJob;
use Illuminate\Console\Command;

class UpdatePageViewStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:update-page-view-statistics';

    /**
     * The legacy command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'app:update-pages-view-statitics',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the pages view statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (config('analytics.analyticsGraph')) {
            UpdateAnalyticsPageViewStatisticsJob::dispatch();
        }

        return self::SUCCESS;
    }
}
