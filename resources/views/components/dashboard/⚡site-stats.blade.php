<?php

use Livewire\Component;
use App\Models\Site;
use App\Services\AnalyticsService;
use Illuminate\Support\Str;

new class extends Component
{
    public $siteId;
    public $site;
    public $period = 7; // 1 (24h), 7, 14, 30, 90
    public $includeBots = false;
    public $chartData = [
        'labels' => [],
        'views' => [],
        'uniques' => [],
    ];
    public $deviceData = [
        'labels' => [],
        'data' => [],
    ];

    public function mount($siteId, AnalyticsService $analytics)
    {
        $this->siteId = $siteId instanceof Site ? $siteId->id : $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
        $this->refreshChartData($analytics);
    }

    public function setPeriod($days, AnalyticsService $analytics)
    {
        $this->period = in_array((int) $days, [1, 7, 14, 30, 90], true) ? (int) $days : 7;
        $this->refreshChartData($analytics);
    }

    public function toggleBots(AnalyticsService $analytics)
    {
        $this->includeBots = ! $this->includeBots;
        $this->refreshChartData($analytics);
    }

    protected function refreshChartData(AnalyticsService $analytics)
    {
        $this->chartData = $analytics->getTimeSeries($this->site, $this->period, $this->includeBots);
        $this->deviceData = $analytics->getDeviceDistribution($this->site, $this->period, $this->includeBots);
    }

    public function render(AnalyticsService $analytics)
    {
        return view('components.dashboard.⚡site-stats', [
            'overview' => $analytics->getOverview($this->site, $this->period, $this->includeBots),
            'topPages' => $analytics->getTopPages($this->site, $this->period, 10, $this->includeBots),
            'topReferrers' => $analytics->getTopReferrers($this->site, $this->period, 10, $this->includeBots),
            'topCountries' => $analytics->getTopCountries($this->site, $this->period, 8, $this->includeBots),
            'browsers' => $analytics->getBrowserDistribution($this->site, $this->period, 5, $this->includeBots),
            'operatingSystems' => $analytics->getOsDistribution($this->site, $this->period, 5, $this->includeBots),
            'campaigns' => $analytics->getCampaignDistribution($this->site, $this->period, 8),
            'recentViews' => $analytics->buildViewQuery($this->site, $this->period, true, $this->includeBots)->latest()->take(15)->get(),
            'deployments' => $this->site->deployments()->latest()->take(5)->get(),
        ]);
    }
};
?>

<div x-data="{
    trafficChart: null,
    deviceChart: null,
    init() {
        this.$nextTick(() => {
            this.buildCharts();
        });

        this.$watch('$wire.chartData', (val) => {
            if (val && this.trafficChart) {
                this.trafficChart.data.labels = val.labels || [];
                this.trafficChart.data.datasets[0].data = val.views || [];
                this.trafficChart.data.datasets[1].data = val.uniques || [];
                this.trafficChart.update();
            } else {
                this.buildCharts();
            }
        });

        this.$watch('$wire.deviceData', (val) => {
            if (val && this.deviceChart) {
                this.deviceChart.data.labels = val.labels || [];
                this.deviceChart.data.datasets[0].data = val.data || [];
                this.deviceChart.update();
            } else {
                this.buildCharts();
            }
        });
    },
    buildCharts() {
        if (typeof Chart === 'undefined') return;

        // Traffic Line Chart
        if (this.$refs.trafficChart) {
            if (this.trafficChart) this.trafficChart.destroy();
            const ctx = this.$refs.trafficChart.getContext('2d');
            
            const indigoGradient = ctx.createLinearGradient(0, 0, 0, 260);
            indigoGradient.addColorStop(0, 'rgba(99, 102, 241, 0.28)');
            indigoGradient.addColorStop(1, 'rgba(99, 102, 241, 0.00)');

            const emeraldGradient = ctx.createLinearGradient(0, 0, 0, 260);
            emeraldGradient.addColorStop(0, 'rgba(16, 185, 129, 0.22)');
            emeraldGradient.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

            this.trafficChart = new Chart(this.$refs.trafficChart, {
                type: 'line',
                data: {
                    labels: this.$wire.chartData.labels || [],
                    datasets: [
                        {
                            label: 'Page Views',
                            data: this.$wire.chartData.views || [],
                            borderColor: '#6366f1',
                            backgroundColor: indigoGradient,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointBackgroundColor: '#6366f1',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Unique Visitors',
                            data: this.$wire.chartData.uniques || [],
                            borderColor: '#10b981',
                            backgroundColor: emeraldGradient,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                color: '#71717a',
                                font: { size: 12, family: 'inherit' }
                            }
                        },
                        tooltip: {
                            padding: 10,
                            boxPadding: 4,
                            usePointStyle: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(150, 150, 150, 0.1)' },
                            ticks: { precision: 0, color: '#a1a1aa' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#a1a1aa' }
                        }
                    }
                }
            });
        }

        // Device Doughnut Chart
        if (this.$refs.deviceChart) {
            if (this.deviceChart) this.deviceChart.destroy();
            this.deviceChart = new Chart(this.$refs.deviceChart, {
                type: 'doughnut',
                data: {
                    labels: this.$wire.deviceData.labels || [],
                    datasets: [{
                        data: this.$wire.deviceData.data || [],
                        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#8b5cf6'],
                        borderWidth: 2,
                        borderColor: 'transparent',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                padding: 14,
                                color: '#71717a',
                                font: { size: 12, family: 'inherit' }
                            }
                        }
                    }
                }
            });
        }
    }
}">
    <!-- Header & Controls -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Analytics</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex items-center gap-3 mt-2">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Site Analytics</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Insights
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Period Filters -->
            <div class="inline-flex rounded-lg p-1 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                <button wire:click="setPeriod(1)" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === 1 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">24h</button>
                <button wire:click="setPeriod(7)" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === 7 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">7d</button>
                <button wire:click="setPeriod(14)" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === 14 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">14d</button>
                <button wire:click="setPeriod(30)" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === 30 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">30d</button>
                <button wire:click="setPeriod(90)" class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === 90 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">90d</button>
            </div>

            <!-- Bot Filter Toggle -->
            <flux:button wire:click="toggleBots" size="sm" :variant="$includeBots ? 'primary' : 'ghost'" title="Toggle bot crawler traffic">
                <svg class="size-4 mr-1 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ $includeBots ? 'Bots: Included' : 'Bots: Excluded' }}
            </flux:button>

            <!-- Export CSV -->
            <a href="{{ route('dashboard.sites.stats.export', ['site' => $site->id, 'days' => $period]) }}" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-white dark:text-zinc-900 transition-colors shadow-xs">
                <flux:icon.arrow-down-tray class="size-3.5" />
                Export CSV
            </a>
        </div>
    </div>

    <!-- Quick Stats Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Page Views -->
        <flux:card class="relative overflow-hidden">
            <div class="flex items-center justify-between text-sm text-zinc-500 mb-1">
                <span>Page Views</span>
                <span class="inline-flex items-center text-xs font-semibold {{ $overview['views_growth'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $overview['views_growth'] >= 0 ? '+' : '' }}{{ $overview['views_growth'] }}%
                </span>
            </div>
            <div class="text-3xl font-black tracking-tight text-indigo-600 dark:text-indigo-400">
                {{ number_format($overview['total_views']) }}
            </div>
            <div class="text-xs text-zinc-400 mt-2">
                {{ number_format($overview['avg_daily_views'], 1) }} daily avg ({{ $period }}d)
            </div>
        </flux:card>

        <!-- Unique Visitors -->
        <flux:card class="relative overflow-hidden">
            <div class="flex items-center justify-between text-sm text-zinc-500 mb-1">
                <span>Unique Visitors</span>
                <span class="inline-flex items-center text-xs font-semibold {{ $overview['visitors_growth'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $overview['visitors_growth'] >= 0 ? '+' : '' }}{{ $overview['visitors_growth'] }}%
                </span>
            </div>
            <div class="text-3xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                {{ number_format($overview['unique_visitors']) }}
            </div>
            <div class="text-xs text-zinc-400 mt-2">
                Distinct visitor IP addresses
            </div>
        </flux:card>

        <!-- Pages Per Visitor -->
        <flux:card class="relative overflow-hidden">
            <div class="flex items-center justify-between text-sm text-zinc-500 mb-1">
                <span>Views / Visitor</span>
                <flux:badge size="sm" color="purple">Depth</flux:badge>
            </div>
            <div class="text-3xl font-black tracking-tight text-purple-600 dark:text-purple-400">
                {{ $overview['views_per_visitor'] }}
            </div>
            <div class="text-xs text-zinc-400 mt-2">
                Average engagement density
            </div>
        </flux:card>

        <!-- Assets & Bandwidth Served -->
        <flux:card class="relative overflow-hidden">
            <div class="flex items-center justify-between text-sm text-zinc-500 mb-1">
                <span>Asset Requests</span>
                <flux:badge size="sm" color="zinc">Assets</flux:badge>
            </div>
            <div class="text-3xl font-black tracking-tight text-zinc-700 dark:text-zinc-300">
                {{ number_format($overview['asset_requests']) }}
            </div>
            <div class="text-xs text-zinc-400 mt-2">
                Static assets served (CSS/JS/images)
            </div>
        </flux:card>
    </div>

    <!-- Traffic and Device Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <flux:card class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Traffic Timeline</h3>
                    <p class="text-xs text-zinc-500">Page views and unique visitors over the last {{ $period === 1 ? '24 hours' : "{$period} days" }}</p>
                </div>
            </div>
            <div class="h-68">
                <canvas x-ref="trafficChart"></canvas>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Device Distribution</h3>
                    <p class="text-xs text-zinc-500">Breakdown by visitor device type</p>
                </div>
            </div>
            <div class="h-68">
                <canvas x-ref="deviceChart"></canvas>
            </div>
        </flux:card>
    </div>

    <!-- Top Pages and Referrers Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Pages Table -->
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Top Visited Pages</h3>
                    <p class="text-xs text-zinc-500">Most frequented URL paths and documents</p>
                </div>
                <flux:badge size="sm" color="indigo">{{ count($topPages) }} Pages</flux:badge>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-xs font-semibold text-zinc-500">
                            <th class="pb-2.5">Path</th>
                            <th class="pb-2.5 text-right">Views</th>
                            <th class="pb-2.5 text-right">Uniques</th>
                            <th class="pb-2.5 text-right w-24">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($topPages as $page)
                            <tr class="group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                <td class="py-2.5 font-mono text-xs text-zinc-900 dark:text-zinc-100 truncate max-w-xs">
                                    <a href="{{ url('/s/'.$site->slug.($page['path'] === '/' ? '' : $page['path'])) }}" target="_blank" class="hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline inline-flex items-center gap-1.5">
                                        <span>{{ $page['path'] }}</span>
                                        <svg class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </td>
                                <td class="py-2.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($page['views']) }}</td>
                                <td class="py-2.5 text-right text-zinc-500 text-xs">{{ number_format($page['unique_visitors']) }}</td>
                                <td class="py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ $page['percentage'] }}%</span>
                                        <div class="w-12 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ min($page['percentage'], 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-zinc-500 text-xs">No page traffic recorded in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        <!-- Top Referrers & Channels -->
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Top Traffic Sources</h3>
                    <p class="text-xs text-zinc-500">Inbound referral domains and search engines</p>
                </div>
                <flux:badge size="sm" color="purple">{{ count($topReferrers) }} Sources</flux:badge>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-xs font-semibold text-zinc-500">
                            <th class="pb-2.5">Domain / Channel</th>
                            <th class="pb-2.5 text-right">Views</th>
                            <th class="pb-2.5 text-right">Uniques</th>
                            <th class="pb-2.5 text-right w-24">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($topReferrers as $ref)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate max-w-xs">{{ $ref['domain'] }}</span>
                                        <flux:badge size="sm" :color="$ref['channel_color']">{{ $ref['channel'] }}</flux:badge>
                                    </div>
                                </td>
                                <td class="py-2.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($ref['views']) }}</td>
                                <td class="py-2.5 text-right text-zinc-500 text-xs">{{ number_format($ref['unique_visitors']) }}</td>
                                <td class="py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ $ref['percentage'] }}%</span>
                                        <div class="w-12 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ min($ref['percentage'], 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-zinc-500 text-xs">No referral traffic recorded in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>

    <!-- Geographic & Technology Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Countries Breakdown -->
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Geographic Distribution</h3>
                    <p class="text-xs text-zinc-500">Visitor traffic by country of origin</p>
                </div>
                <flux:badge size="sm" color="emerald">{{ count($topCountries) }} Countries</flux:badge>
            </div>

            <div class="space-y-3">
                @forelse($topCountries as $country)
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-base">{{ $country['flag'] }}</span>
                            <span class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate">{{ $country['name'] }}</span>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-500 uppercase">{{ $country['code'] }}</span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($country['views']) }}</span>
                            <div class="w-20 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min($country['percentage'], 100) }}%"></div>
                            </div>
                            <span class="text-xs text-zinc-400 w-10 text-right">{{ $country['percentage'] }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-zinc-500 text-xs">No geographic data detected for this period.</div>
                @endforelse
            </div>
        </flux:card>

        <!-- Browsers & Operating Systems -->
        <flux:card>
            <div class="mb-4">
                <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Browsers & Platforms</h3>
                <p class="text-xs text-zinc-500">Software environments used by visitors</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Browsers -->
                <div>
                    <div class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Top Browsers</div>
                    <div class="space-y-2.5">
                        @forelse($browsers as $b)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $b['name'] }}</span>
                                    <span class="text-zinc-500">{{ $b['percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ min($b['percentage'], 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-xs text-zinc-400 py-3">No browser data.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Operating Systems -->
                <div>
                    <div class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Top Operating Systems</div>
                    <div class="space-y-2.5">
                        @forelse($operatingSystems as $os)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $os['name'] }}</span>
                                    <span class="text-zinc-500">{{ $os['percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ min($os['percentage'], 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-xs text-zinc-400 py-3">No OS data.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- UTM Campaigns (Shown if present) -->
    @if($campaigns->isNotEmpty())
        <flux:card class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Campaign Performance (UTM)</h3>
                    <p class="text-xs text-zinc-500">Inbound marketing campaigns tracked via UTM query parameters</p>
                </div>
                <flux:badge size="sm" color="amber">{{ count($campaigns) }} Campaigns</flux:badge>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-xs font-semibold text-zinc-500">
                            <th class="pb-2.5">Campaign</th>
                            <th class="pb-2.5">Source</th>
                            <th class="pb-2.5">Medium</th>
                            <th class="pb-2.5 text-right">Views</th>
                            <th class="pb-2.5 text-right">Uniques</th>
                            <th class="pb-2.5 text-right w-24">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($campaigns as $camp)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                <td class="py-2.5 font-medium text-xs text-zinc-900 dark:text-zinc-100">{{ $camp['campaign'] }}</td>
                                <td class="py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $camp['source'] }}</td>
                                <td class="py-2.5 text-xs text-zinc-500 font-mono">{{ $camp['medium'] }}</td>
                                <td class="py-2.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($camp['views']) }}</td>
                                <td class="py-2.5 text-right text-zinc-500 text-xs">{{ number_format($camp['unique_visitors']) }}</td>
                                <td class="py-2.5 text-right text-xs text-zinc-500">{{ $camp['percentage'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    <!-- Deployment History -->
    <flux:card class="mb-8">
        <h3 class="font-bold mb-4 text-base text-zinc-900 dark:text-zinc-100">Deployment History</h3>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Source</flux:table.column>
                    <flux:table.column>Commit / Details</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($deployments as $deployment)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <div class="size-2 rounded-full {{ $deployment->status === 'success' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                    <span class="font-medium capitalize text-sm">{{ $deployment->status }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    @if(str_contains($deployment->source, 'github'))
                                        <svg class="size-4 text-zinc-400" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                                    @else
                                        <flux:icon.arrow-up-tray class="size-4 text-zinc-400" />
                                    @endif
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ str_replace('_', ' ', ucfirst($deployment->source)) }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm font-medium">{{ Str::limit($deployment->commit_message, 50) }}</div>
                                @if($deployment->commit_hash)
                                    <div class="text-xs font-mono text-zinc-400 mt-0.5">{{ Str::limit($deployment->commit_hash, 7, '') }}</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">{{ $deployment->created_at->diffForHumans() }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center py-8 text-zinc-500">No deployments recorded yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Recent Activity Stream Table -->
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Live Request Activity</h3>
                <p class="text-xs text-zinc-500">Most recent real-time visits and asset requests</p>
            </div>
            <flux:badge size="sm" color="zinc">Last 15 Requests</flux:badge>
        </div>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Path</flux:table.column>
                    <flux:table.column>Country</flux:table.column>
                    <flux:table.column>Platform / Device</flux:table.column>
                    <flux:table.column>Source</flux:table.column>
                    <flux:table.column>Time</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($recentViews as $view)
                        <flux:table.row>
                            <flux:table.cell>
                                @if($view->is_bot)
                                    <flux:badge size="sm" color="amber">Bot</flux:badge>
                                @elseif($view->is_quiet)
                                    <flux:badge size="sm" color="zinc">Asset</flux:badge>
                                @else
                                    <flux:badge size="sm" color="indigo">Page View</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-mono text-xs text-zinc-800 dark:text-zinc-200 truncate max-w-[200px] block" title="{{ $view->path }}">
                                    {{ $view->path ?: '/' }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm">{{ app(\App\Services\AnalyticsService::class)->getCountryFlag($view->country_code) }}</span>
                                    <span class="text-xs font-mono text-zinc-600 dark:text-zinc-400">{{ $view->country_code ?: '—' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs text-zinc-700 dark:text-zinc-300">
                                    <span class="font-medium">{{ $view->browser ?: 'Unknown' }}</span>
                                    <span class="text-zinc-400">on</span>
                                    <span>{{ $view->os ?: 'OS' }}</span>
                                </div>
                                <div class="text-[11px] text-zinc-400">{{ $view->device ?: 'Desktop' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs truncate max-w-[160px] text-zinc-600 dark:text-zinc-400" title="{{ $view->referer ?: 'Direct' }}">
                                    {{ $view->referer_domain ?: 'Direct' }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500 whitespace-nowrap">
                                {{ $view->created_at?->diffForHumans() }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8 text-zinc-500">No requests recorded yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>