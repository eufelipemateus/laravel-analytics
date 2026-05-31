<?php

namespace AndreasElia\Analytics\Tests\Feature;

use AndreasElia\Analytics\Database\Factories\PageViewFactory;
use AndreasElia\Analytics\Jobs\UpdateAnalyticsPageViewStatisticsJob;
use AndreasElia\Analytics\Models\AnalyticsPageViewStatistics;
use AndreasElia\Analytics\Tests\TestCase;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_replaces_statistics_when_a_time_window_is_processed_again()
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-30 11:55:00'), function () {
            PageViewFactory::new()->count(2)->create([
                'uri' => '/test',
            ]);
        });

        $this->travelTo(CarbonImmutable::parse('2026-05-30 12:04:00'), function () {
            (new UpdateAnalyticsPageViewStatisticsJob())->handle();
            (new UpdateAnalyticsPageViewStatisticsJob())->handle();
        });

        $this->assertDatabaseCount('analytics_page_view_statistics', 1);
        $this->assertDatabaseHas('analytics_page_view_statistics', [
            'time_window' => '2026-05-30 12:00:00',
            'page' => '/test',
            'page_views' => 2,
        ]);
    }

    #[Test]
    public function it_does_not_store_an_empty_time_window()
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-30 12:04:00'), function () {
            (new UpdateAnalyticsPageViewStatisticsJob())->handle();
        });

        $this->assertDatabaseCount('analytics_page_view_statistics', 0);
    }

    #[Test]
    public function it_aligns_graph_datasets_with_their_time_windows()
    {
        AnalyticsPageViewStatistics::query()->create([
            'time_window' => '2026-05-30 12:00:00',
            'page' => '/first',
            'page_views' => 2,
        ]);
        AnalyticsPageViewStatistics::query()->create([
            'time_window' => '2026-05-30 12:10:00',
            'page' => '/second',
            'page_views' => 3,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-05-30 12:20:00'), function () {
            $this->get('analytics')
                ->assertOk()
                ->assertViewHas('graph', function (object $graph): bool {
                    $datasets = collect($graph->datasets);

                    return $graph->labels->all() === ['05-30 12:00', '05-30 12:10']
                        && $datasets->firstWhere('label', '/first')['data']->all() === [2, 0]
                        && $datasets->firstWhere('label', '/second')['data']->all() === [0, 3];
                });
        });
    }
}
