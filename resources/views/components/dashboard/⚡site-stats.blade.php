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
    isDark() {
        return document.documentElement.classList.contains('dark');
    },
    init() {
        this.$nextTick(() => {
            this.buildCharts();
        });

        const observer = new MutationObserver(() => {
            this.buildCharts();
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

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
        const dark = this.isDark();
        const tickColor = dark ? '#a1a1aa' : '#71717a';
        const gridColor = dark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';

        // Traffic Line Chart
        if (this.$refs.trafficChart) {
            if (this.trafficChart) this.trafficChart.destroy();
            const ctx = this.$refs.trafficChart.getContext('2d');
            
            const indigoGradient = ctx.createLinearGradient(0, 0, 0, 260);
            indigoGradient.addColorStop(0, dark ? 'rgba(99, 102, 241, 0.35)' : 'rgba(99, 102, 241, 0.2)');
            indigoGradient.addColorStop(1, 'rgba(99, 102, 241, 0.00)');

            const emeraldGradient = ctx.createLinearGradient(0, 0, 0, 260);
            emeraldGradient.addColorStop(0, dark ? 'rgba(16, 185, 129, 0.3)' : 'rgba(16, 185, 129, 0.18)');
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
                            borderWidth: 2.5,
                            pointBackgroundColor: '#6366f1',
                            pointBorderColor: dark ? '#18181b' : '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 3.5,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Unique Visitors',
                            data: this.$wire.chartData.uniques || [],
                            borderColor: '#10b981',
                            backgroundColor: emeraldGradient,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: dark ? '#18181b' : '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 3.5,
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
                                boxWidth: 8,
                                boxHeight: 8,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                color: tickColor,
                                font: { size: 12, weight: '500' }
                            }
                        },
                        tooltip: {
                            padding: 10,
                            boxPadding: 4,
                            usePointStyle: true,
                            backgroundColor: dark ? '#18181b' : '#ffffff',
                            titleColor: dark ? '#ffffff' : '#18181b',
                            bodyColor: dark ? '#d4d4d8' : '#3f3f46',
                            borderColor: dark ? '#27272a' : '#e4e4e7',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: { precision: 0, color: tickColor, font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: tickColor, font: { size: 11 } }
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
                        borderColor: dark ? '#18181b' : '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 8,
                                padding: 14,
                                color: tickColor,
                                font: { size: 11, weight: '500' }
                            }
                        }
                    }
                }
            });
        }
    }
}">
    <!-- Subnavigation Header -->
    <x-site-subnav :site="$site" current="stats" />

    <!-- Controls Bar -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40 shadow-xs">
                <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Live Telemetry</span>
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Period Filters -->
            <div class="inline-flex rounded-xl p-1 bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80">
                <button wire:click="setPeriod(1)" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors cursor-pointer {{ $period === 1 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">24h</button>
                <button wire:click="setPeriod(7)" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors cursor-pointer {{ $period === 7 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">7d</button>
                <button wire:click="setPeriod(14)" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors cursor-pointer {{ $period === 14 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">14d</button>
                <button wire:click="setPeriod(30)" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors cursor-pointer {{ $period === 30 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">30d</button>
                <button wire:click="setPeriod(90)" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors cursor-pointer {{ $period === 90 ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">90d</button>
            </div>

            <!-- Bot Filter Toggle -->
            <button 
                type="button"
                wire:click="toggleBots" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl border transition-colors cursor-pointer {{ $includeBots ? 'bg-indigo-50 border-indigo-200 text-indigo-700 dark:bg-indigo-950/40 dark:border-indigo-800 dark:text-indigo-400' : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}"
                title="Toggle bot crawler traffic"
            >
                <flux:icon.cpu-chip class="size-3.5" />
                <span>{{ $includeBots ? 'Bots: Included' : 'Bots: Excluded' }}</span>
            </button>

            <!-- Export CSV -->
            <a 
                href="{{ route('dashboard.sites.stats.export', ['site' => $site->id, 'days' => $period]) }}" 
                target="_blank" 
                download 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-white dark:text-zinc-900 transition-colors shadow-xs"
            >
                <flux:icon.arrow-down-tray class="size-3.5" />
                Export CSV
            </a>
        </div>
    </div>

    <!-- Quick Stats Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Page Views -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Page Views</span>
                <span class="inline-flex items-center gap-0.5 text-xs font-semibold {{ $overview['views_growth'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    <flux:icon.arrow-trending-up class="size-3 {{ $overview['views_growth'] < 0 ? 'rotate-180 text-rose-500' : '' }}" />
                    {{ $overview['views_growth'] >= 0 ? '+' : '' }}{{ $overview['views_growth'] }}%
                </span>
            </div>
            <div class="text-3xl font-black tracking-tight text-indigo-600 dark:text-indigo-400">
                {{ number_format($overview['total_views']) }}
            </div>
            <div class="text-xs text-zinc-400 mt-2 font-mono">
                {{ number_format($overview['avg_daily_views'], 1) }} daily avg ({{ $period }}d)
            </div>
        </div>

        <!-- Unique Visitors -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Unique Visitors</span>
                <span class="inline-flex items-center gap-0.5 text-xs font-semibold {{ $overview['visitors_growth'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    <flux:icon.arrow-trending-up class="size-3 {{ $overview['visitors_growth'] < 0 ? 'rotate-180 text-rose-500' : '' }}" />
                    {{ $overview['visitors_growth'] >= 0 ? '+' : '' }}{{ $overview['visitors_growth'] }}%
                </span>
            </div>
            <div class="text-3xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                {{ number_format($overview['unique_visitors']) }}
            </div>
            <div class="text-xs text-zinc-400 mt-2 font-mono">
                Distinct IP fingerprints
            </div>
        </div>

        <!-- Pages Per Visitor -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Views / Visitor</span>
                <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400">Depth</span>
            </div>
            <div class="text-3xl font-black tracking-tight text-purple-600 dark:text-purple-400">
                {{ $overview['views_per_visitor'] }}
            </div>
            <div class="text-xs text-zinc-400 mt-2 font-mono">
                Average engagement density
            </div>
        </div>

        <!-- Assets & Bandwidth Served -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 mb-2">
                <span>Asset Requests</span>
                <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">Static</span>
            </div>
            <div class="text-3xl font-black tracking-tight text-zinc-700 dark:text-zinc-300">
                {{ number_format($overview['asset_requests']) }}
            </div>
            <div class="text-xs text-zinc-400 mt-2 font-mono">
                CSS, JS, & image hits
            </div>
        </div>
    </div>

    <!-- Traffic and Device Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="glass-card rounded-2xl p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.chart-bar class="size-4 text-indigo-500" />
                        Traffic Timeline
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Page views and unique visitors over the last {{ $period === 1 ? '24 hours' : "{$period} days" }}</p>
                </div>
            </div>
            <div class="h-68">
                <canvas x-ref="trafficChart"></canvas>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.computer-desktop class="size-4 text-emerald-500" />
                        Device Types
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Breakdown by client device type</p>
                </div>
            </div>
            <div class="h-68">
                <canvas x-ref="deviceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Pages and Referrers Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Pages Table -->
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.document-text class="size-4 text-indigo-500" />
                        Top Visited Pages
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Most frequented URL paths and documents</p>
                </div>
                <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">{{ count($topPages) }} Pages</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
                            <th class="pb-2.5">Path</th>
                            <th class="pb-2.5 text-right">Views</th>
                            <th class="pb-2.5 text-right">Uniques</th>
                            <th class="pb-2.5 text-right w-28">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse($topPages as $page)
                            <tr class="group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-2.5 font-mono text-xs text-zinc-900 dark:text-zinc-100 truncate max-w-xs">
                                    <a href="{{ url('/s/'.$site->slug.($page['path'] === '/' ? '' : $page['path'])) }}" target="_blank" class="hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline inline-flex items-center gap-1.5">
                                        <span>{{ $page['path'] }}</span>
                                        <flux:icon.arrow-top-right-on-square class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </a>
                                </td>
                                <td class="py-2.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($page['views']) }}</td>
                                <td class="py-2.5 text-right text-zinc-500 text-xs font-mono">{{ number_format($page['unique_visitors']) }}</td>
                                <td class="py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 font-mono">{{ $page['percentage'] }}%</span>
                                        <div class="w-14 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ min($page['percentage'], 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-zinc-400 text-xs">No page traffic recorded in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Referrers & Channels -->
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.arrow-path-rounded-square class="size-4 text-purple-500" />
                        Top Traffic Sources
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Inbound referral domains and search channels</p>
                </div>
                <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">{{ count($topReferrers) }} Sources</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
                            <th class="pb-2.5">Domain / Channel</th>
                            <th class="pb-2.5 text-right">Views</th>
                            <th class="pb-2.5 text-right">Uniques</th>
                            <th class="pb-2.5 text-right w-28">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse($topReferrers as $ref)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate max-w-xs">{{ $ref['domain'] }}</span>
                                        <flux:badge size="sm" :color="$ref['channel_color']">{{ $ref['channel'] }}</flux:badge>
                                    </div>
                                </td>
                                <td class="py-2.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($ref['views']) }}</td>
                                <td class="py-2.5 text-right text-zinc-500 text-xs font-mono">{{ number_format($ref['unique_visitors']) }}</td>
                                <td class="py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 font-mono">{{ $ref['percentage'] }}%</span>
                                        <div class="w-14 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ min($ref['percentage'], 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-zinc-400 text-xs">No referral traffic recorded in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Geographic & Technology Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Countries Breakdown -->
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.globe-alt class="size-4 text-emerald-500" />
                        Geographic Distribution
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Visitor traffic by country of origin</p>
                </div>
                <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">{{ count($topCountries) }} Countries</span>
            </div>

            <div class="space-y-3">
                @forelse($topCountries as $country)
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-base">{{ $country['flag'] }}</span>
                            <span class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate">{{ $country['name'] }}</span>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-500 uppercase font-semibold">{{ $country['code'] }}</span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 font-mono">{{ number_format($country['views']) }}</span>
                            <div class="w-20 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min($country['percentage'], 100) }}%"></div>
                            </div>
                            <span class="text-xs text-zinc-400 w-10 text-right font-mono">{{ $country['percentage'] }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-zinc-400 text-xs">No geographic data detected for this period.</div>
                @endforelse
            </div>
        </div>

        <!-- Browsers & Operating Systems -->
        <div class="glass-card rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <flux:icon.window class="size-4 text-amber-500" />
                    Browsers & Platforms
                </h3>
                <p class="text-xs text-zinc-500 mt-0.5">Software environments used by visitors</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Browsers -->
                <div>
                    <div class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Top Browsers</div>
                    <div class="space-y-2.5">
                        @forelse($browsers as $b)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1 font-medium">
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ $b['name'] }}</span>
                                    <span class="text-zinc-500 font-mono">{{ $b['percentage'] }}%</span>
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
                                <div class="flex items-center justify-between text-xs mb-1 font-medium">
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ $os['name'] }}</span>
                                    <span class="text-zinc-500 font-mono">{{ $os['percentage'] }}%</span>
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
        </div>
    </div>

    <!-- UTM Campaigns (Shown if present) -->
    @if($campaigns->isNotEmpty())
        <div class="glass-card rounded-2xl p-5 mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.tag class="size-4 text-amber-500" />
                        Campaign Performance (UTM)
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Inbound marketing campaigns tracked via UTM query parameters</p>
                </div>
                <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">{{ count($campaigns) }} Campaigns</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
                            <th class="pb-2.5">Campaign</th>
                            <th class="pb-2.5">Source</th>
                            <th class="pb-2.5">Medium</th>
                            <th class="pb-2.5 text-right">Views</th>
                            <th class="pb-2.5 text-right">Uniques</th>
                            <th class="pb-2.5 text-right w-24">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @foreach($campaigns as $camp)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-2.5 font-medium text-xs text-zinc-900 dark:text-zinc-100">{{ $camp['campaign'] }}</td>
                                <td class="py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $camp['source'] }}</td>
                                <td class="py-2.5 text-xs text-zinc-500 font-mono">{{ $camp['medium'] }}</td>
                                <td class="py-2.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($camp['views']) }}</td>
                                <td class="py-2.5 text-right text-zinc-500 text-xs font-mono">{{ number_format($camp['unique_visitors']) }}</td>
                                <td class="py-2.5 text-right text-xs text-zinc-500 font-mono">{{ $camp['percentage'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Deployment History -->
    <div class="glass-card rounded-2xl p-5 mb-8">
        <h3 class="font-bold mb-4 text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
            <flux:icon.archive-box class="size-4 text-indigo-500" />
            Deployment History
        </h3>
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
                                        <flux:icon.folder-git-2 class="size-4 text-zinc-400" />
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
                            <flux:table.cell colspan="4" class="text-center py-8 text-zinc-400 text-xs">No deployments recorded yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    <!-- Recent Activity Stream Table -->
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <flux:icon.bolt class="size-4 text-amber-500" />
                    Live Request Activity
                </h3>
                <p class="text-xs text-zinc-500 mt-0.5">Most recent real-time visits and asset requests</p>
            </div>
            <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">Last 15 Requests</span>
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
                                    <span class="text-base">{{ app(\App\Services\AnalyticsService::class)->getCountryFlag($view->country_code) }}</span>
                                    <span class="text-xs font-mono text-zinc-600 dark:text-zinc-400 font-medium">{{ $view->country_code ?: '—' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs text-zinc-700 dark:text-zinc-300">
                                    <span class="font-medium">{{ $view->browser ?: 'Unknown' }}</span>
                                    <span class="text-zinc-400">on</span>
                                    <span>{{ $view->os ?: 'OS' }}</span>
                                </div>
                                <div class="text-[11px] text-zinc-400 font-mono">{{ $view->device ?: 'Desktop' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs truncate max-w-[160px] text-zinc-600 dark:text-zinc-400" title="{{ $view->referer ?: 'Direct' }}">
                                    {{ $view->referer_domain ?: 'Direct' }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500 whitespace-nowrap font-mono">
                                {{ $view->created_at?->diffForHumans() }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8 text-zinc-400 text-xs">No requests recorded yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>