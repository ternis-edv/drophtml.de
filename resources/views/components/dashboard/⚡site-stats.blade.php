<?php

use Livewire\Component;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new class extends Component
{
    public $siteId;
    public $site;
    public $period = 7; // days

    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
    }

    public function getChartData()
    {
        // Views over time
        $viewsByDate = $this->site->siteViews()
            ->where('created_at', '>=', now()->subDays($this->period))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Fill in missing dates
        $labels = [];
        $data = [];
        for ($i = $this->period; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            $data[] = $viewsByDate[$date] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getDeviceData()
    {
        $views = $this->site->siteViews()
            ->where('created_at', '>=', now()->subDays($this->period))
            ->get();

        $devices = [
            'Mobile' => 0,
            'Desktop' => 0,
            'Tablet' => 0,
            'Other' => 0,
        ];

        foreach ($views as $view) {
            $ua = strtolower($view->user_agent);
            if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
                $devices['Mobile']++;
            } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
                $devices['Tablet']++;
            } else {
                $devices['Desktop']++;
            }
        }

        return [
            'labels' => array_keys($devices),
            'data' => array_values($devices),
        ];
    }

    public function render()
    {
        $recentViews = $this->site->siteViews()->latest()->take(10)->get();
        $totalViews = $this->site->siteViews()->count();
        $uniqueVisitors = $this->site->siteViews()->distinct('ip_address')->count();

        return view('components.dashboard.⚡site-stats', [
            'recentViews' => $recentViews,
            'totalViews' => $totalViews,
            'uniqueVisitors' => $uniqueVisitors,
            'chartData' => $this->getChartData(),
            'deviceData' => $this->getDeviceData(),
        ]);
    }
};
?>

<div x-data="{ 
    chartData: {{ json_encode($chartData) }},
    deviceData: {{ json_encode($deviceData) }},
    init() {
        this.renderTrafficChart();
        this.renderDeviceChart();
    },
    renderTrafficChart() {
        new Chart(this.$refs.trafficChart, {
            type: 'line',
            data: {
                labels: this.chartData.labels,
                datasets: [{
                    label: 'Views',
                    data: this.chartData.data,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    },
    renderDeviceChart() {
        new Chart(this.$refs.deviceChart, {
            type: 'doughnut',
            data: {
                labels: this.deviceData.labels,
                datasets: [{
                    data: this.deviceData.data,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#6366f1']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
}">
    <head>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>

    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Statistics</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <h1 class="text-2xl font-bold mt-2 text-zinc-900 dark:text-zinc-100">Site Statistics</h1>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <flux:card>
            <div class="text-sm text-zinc-500 mb-1">Total Views</div>
            <div class="text-3xl font-black">{{ number_format($totalViews) }}</div>
        </flux:card>
        <flux:card>
            <div class="text-sm text-zinc-500 mb-1">Unique Visitors</div>
            <div class="text-3xl font-black">{{ number_format($uniqueVisitors) }}</div>
        </flux:card>
        <flux:card>
            <div class="text-sm text-zinc-500 mb-1">Avg. Daily Views</div>
            <div class="text-3xl font-black">{{ number_format($totalViews / max($period, 1), 1) }}</div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Main Traffic Chart -->
        <flux:card class="lg:col-span-2">
            <h3 class="font-bold mb-6">Traffic Over Time (Last {{ $period }} Days)</h3>
            <div class="h-64">
                <canvas x-ref="trafficChart"></canvas>
            </div>
        </flux:card>

        <!-- Device Distribution -->
        <flux:card>
            <h3 class="font-bold mb-6">Device Usage</h3>
            <div class="h-64">
                <canvas x-ref="deviceChart"></canvas>
            </div>
        </flux:card>
    </div>

    <!-- Recent Visits Table -->
    <flux:card>
        <h3 class="font-bold mb-6">Recent Visits</h3>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Time</flux:table.column>
                    <flux:table.column>IP Address</flux:table.column>
                    <flux:table.column>Referer</flux:table.column>
                    <flux:table.column>Device</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($recentViews as $view)
                        <flux:table.row>
                            <flux:table.cell class="text-sm text-zinc-500">{{ $view->created_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell class="text-sm font-mono">{{ $view->ip_address }}</flux:table.cell>
                            <flux:table.cell class="text-sm truncate max-w-xs">{{ $view->referer ?: 'Direct' }}</flux:table.cell>
                            <flux:table.cell class="text-sm truncate max-w-xs text-zinc-400">{{ Str::limit($view->user_agent, 30) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center py-8 text-zinc-500">No views recorded yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>