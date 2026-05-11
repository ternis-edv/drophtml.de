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
        // Views over time (only non-quiet views)
        $viewsByDate = $this->site->siteViews()
            ->where('is_quiet', false)
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
        // Only non-quiet views for device distribution
        $views = $this->site->siteViews()
            ->where('is_quiet', false)
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
        $recentViews = $this->site->siteViews()->latest()->take(20)->get();
        $totalViews = $this->site->siteViews()->where('is_quiet', false)->count();
        $uniqueVisitors = $this->site->siteViews()->where('is_quiet', false)->distinct('ip_address')->count();

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
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
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
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#6366f1'],
                        borderWidth: 0,
                        hoverOffset: 10
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

    <!-- Deployment History -->
    <flux:card class="mb-8">
        <h3 class="font-bold mb-6">Deployment History</h3>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Source</flux:table.column>
                    <flux:table.column>Commit</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($site->deployments()->latest()->take(10)->get() as $deployment)
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
                                    <div class="text-xs font-mono text-zinc-400 mt-1">{{ Str::limit($deployment->commit_hash, 7, '') }}</div>
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
        <h3 class="font-bold mb-6">Recent Activity</h3>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Time</flux:table.column>
                    <flux:table.column>IP Address</flux:table.column>
                    <flux:table.column>Referer</flux:table.column>
                    <flux:table.column>Device</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($recentViews as $view)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$view->is_quiet ? 'zinc' : 'blue'">
                                    {{ $view->is_quiet ? 'Asset' : 'Page' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">{{ $view->created_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell class="text-sm font-mono">{{ $view->ip_address }}</flux:table.cell>
                            <flux:table.cell class="text-sm truncate max-w-xs">{{ $view->referer ?: 'Direct' }}</flux:table.cell>
                            <flux:table.cell class="text-sm truncate max-w-xs text-zinc-400">{{ Str::limit($view->user_agent, 30) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8 text-zinc-500">No views recorded yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>