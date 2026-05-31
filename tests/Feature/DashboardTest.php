<?php

namespace AndreasElia\Analytics\Tests\Feature;

use AndreasElia\Analytics\Database\Factories\PageViewFactory;
use AndreasElia\Analytics\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        PageViewFactory::new()->count(2)->create([
            'session' => 'abc123',
        ]);

        $this->travelTo(now()->subDay(), function () {
            PageViewFactory::new()->create();
        });

        $this->travelTo(now()->subDays(5), function () {
            PageViewFactory::new()->count(2)
                ->sequence(
                    ['uri' => '/test1'],
                    ['uri' => '/test2']
                )
                ->create(['session' => 'foo']);
        });

        $this->travelTo(now()->subWeeks(3), function () {
            PageViewFactory::new()->create();
        });
    }

    #[Test]
    public function it_can_get_data_from_today()
    {
        $this->get('analytics')
            ->assertViewHas('period', 'today')
            ->assertViewHas('stats', [
                [
                    'key' => 'Last 10 minutes',
                    'value' => 1,
                ],
                [
                    'key' => 'Last 1 hour',
                    'value' => 1,
                ],
                [
                    'key' => 'Unique Users',
                    'value' => 1,
                ],
                [
                    'key' => 'Page Views',
                    'value' => 2,
                ],
            ]);
    }

    #[Test]
    public function it_can_get_data_from_yesterday()
    {
        $this->get(route('analytics', ['period' => 'yesterday']))
            ->assertViewHas('period', 'yesterday')
            ->assertViewHas('stats', [
                [
                    'key' => 'Last 10 minutes',
                    'value' => 1,
                ],
                [
                    'key' => 'Last 1 hour',
                    'value' => 1,
                ],
                [
                    'key' => 'Unique Users',
                    'value' => 1,
                ],
                [
                    'key' => 'Page Views',
                    'value' => 1,
                ],
            ]);
    }

    #[Test]
    public function it_can_get_data_for_1_week()
    {
        $this->get(route('analytics', ['period' => '1_week']))
            ->assertViewHas('period', '1_week')
            ->assertViewHas('stats', [
                [
                    'key' => 'Last 10 minutes',
                    'value' => 1,
                ],
                [
                    'key' => 'Last 1 hour',
                    'value' => 1,
                ],
                [
                    'key' => 'Unique Users',
                    'value' => 3,
                ],
                [
                    'key' => 'Page Views',
                    'value' => 5,
                ],
            ]);
    }

    #[Test]
    public function it_can_get_data_for_30_days()
    {
        $this->get(route('analytics', ['period' => '30_days']))
            ->assertViewHas('period', '30_days')
            ->assertViewHas('stats', [
                [
                    'key' => 'Last 10 minutes',
                    'value' => 1,
                ],
                [
                    'key' => 'Last 1 hour',
                    'value' => 1,
                ],
                [
                    'key' => 'Unique Users',
                    'value' => 4,
                ],
                [
                    'key' => 'Page Views',
                    'value' => 6,
                ],
            ]);
    }

    #[Test]
    public function it_can_get_data_for_30_days_filtered_by_uri()
    {
        $this->get(route('analytics', [
            'period' => '30_days',
            'uri' => '/test1',
        ]))
            ->assertViewHas('period', '30_days')
            ->assertViewHas('uri', '/test1')
            ->assertViewHas('stats', [
                [
                    'key' => 'Last 10 minutes',
                    'value' => 1,
                ],
                [
                    'key' => 'Last 1 hour',
                    'value' => 1,
                ],
                [
                    'key' => 'Unique Users',
                    'value' => 1,
                ],
                [
                    'key' => 'Page Views',
                    'value' => 1,
                ],
            ]);
    }

    #[Test]
    public function it_can_view_sources()
    {
        $this->get(route('analytics', [
            'period' => '30_days',
            'uri' => '/test1',
        ]))
            ->assertSeeText('example.com')
            ->assertSee('<h3 class="text-lg font-medium leading-6 text-gray-900">Sources</h3>', false)
            ->assertSee('<a href="https://example.com" target="_blank" class="hover:underline">', $escaped = false);
    }

    #[Test]
    public function it_wont_show_sources_if_ignored()
    {
        config()->set('analytics.ignoredColumns', ['source']);
        $this->get(route('analytics', [
            'period' => '30_days',
            'uri' => '/test1',
        ]))
            ->assertViewHas('sources', collect())
            ->assertDontSee('<h3 class="text-lg font-medium leading-6 text-gray-900">Sources</h3>', false);
    }

    #[Test]
    public function it_falls_back_to_today_for_an_invalid_period()
    {
        $this->get(route('analytics', ['period' => 'invalid']))
            ->assertOk()
            ->assertViewHas('period', 'today');
    }

    #[Test]
    public function it_ignores_array_filters()
    {
        $this->get(route('analytics', [
            'period' => ['invalid'],
            'uri' => ['invalid'],
        ]))
            ->assertOk()
            ->assertViewHas('period', 'today')
            ->assertViewHas('uri', null);
    }
}
