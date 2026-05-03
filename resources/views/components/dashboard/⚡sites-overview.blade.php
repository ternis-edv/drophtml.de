<?php

use Livewire\Component;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use App\Models\ActivityLog;

new class extends Component
{
    public function deleteSite($id)
    {
        $site = auth()->user()->sites()->findOrFail($id);
        
        // Remove storage
        Storage::disk('public')->deleteDirectory($site->path);
        
        // Log action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => null,
            'action' => 'site_deleted',
            'description' => "Deleted site: {$site->original_name} ({$site->slug})",
            'ip_address' => request()->ip(),
        ]);
        
        $site->delete();
        $this->dispatch('site-deleted');
    }

    public function extendExpiry($id, $days)
    {
        $site = auth()->user()->sites()->findOrFail($id);
        
        $site->expires_at = ($site->expires_at && $site->expires_at->isFuture()) 
            ? $site->expires_at->addDays($days) 
            : now()->addDays($days);
            
        $site->save();
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $site->id,
            'action' => 'expiry_extended',
            'description' => "Extended expiry by {$days} days for site: {$site->slug}",
            'ip_address' => request()->ip(),
        ]);
        
        Flux::toast("Expiry extended by {$days} days.");
    }

    public function render()
    {
        $sites = auth()->user()->sites()->latest()->get();
        $totalViews = $sites->sum('views');
        
        return view('components.dashboard.⚡sites-overview', [
            'sites' => $sites,
            'totalViews' => $totalViews,
            'totalSites' => $sites->count(),
        ]);
    }
};
?>

<div>
    <!-- Stats row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                    <flux:icon.globe-alt class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Total Sites</div>
                    <div class="text-2xl font-bold">{{ $totalSites }}</div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg">
                    <flux:icon.eye class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Total Views</div>
                    <div class="text-2xl font-bold">{{ number_format($totalViews) }}</div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg">
                    <flux:icon.link class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-sm text-zinc-500">Active Domains</div>
                    <div class="text-2xl font-bold">0</div>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Sites Table -->
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Your Sites</h2>
            
            <div class="flex gap-2">
                @if(auth()->user()->github_id)
                    <flux:modal.trigger name="github-deploy-modal">
                        <flux:button variant="ghost" size="sm" icon="github">Deploy from GitHub</flux:button>
                    </flux:modal.trigger>
                @endif

                <flux:modal.trigger name="upload-site-modal">
                    <flux:button variant="primary" size="sm" icon="plus">Upload New Site</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        @if(auth()->user()->github_id)
            <flux:modal name="github-deploy-modal" class="md:w-[700px]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Deploy from GitHub</flux:heading>
                        <flux:subheading>Select a repository to deploy as a new site.</flux:subheading>
                    </div>

                    <livewire:dashboard.github-deploy @site-published="Flux.modal('github-deploy-modal').close()" />
                </div>
            </flux:modal>
        @endif

        @if($sites->isEmpty())
            <div class="py-8 text-center text-zinc-500">
                You haven't uploaded any sites yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name / Slug</flux:table.column>
                        <flux:table.column>Views</flux:table.column>
                        <flux:table.column>Expires In</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column class="text-right">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($sites as $site)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="font-medium">{{ $site->original_name ?: 'Unnamed Site' }}</div>
                                    <div class="text-xs text-zinc-500">
                                        <a href="/s/{{ $site->slug }}" target="_blank" class="hover:underline">/s/{{ $site->slug }}</a>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ number_format($site->views) }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($site->is_permanent)
                                        <flux:badge size="sm" color="purple">Permanent</flux:badge>
                                    @elseif($site->expires_at)
                                        <div class="text-sm {{ $site->expires_at->isPast() ? 'text-red-500' : ($site->expires_at->diffInHours() < 24 ? 'text-amber-500' : 'text-zinc-500') }}" 
                                             x-data="{ 
                                                expiresAt: {{ $site->expires_at->timestamp * 1000 }},
                                                now: Date.now(),
                                                timer: null,
                                                get countdown() {
                                                    let diff = this.expiresAt - this.now;
                                                    if (diff <= 0) return 'Expired';
                                                    
                                                    let days = Math.floor(diff / (1000 * 60 * 60 * 24));
                                                    let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                    let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                                    let seconds = Math.floor((diff % (1000 * 60)) / 1000);
                                                    
                                                    if (days > 0) return `${days}d ${hours}h`;
                                                    return `${hours}h ${minutes}m ${seconds}s`;
                                                }
                                             }"
                                             x-init="timer = setInterval(() => { now = Date.now() }, 1000)"
                                             x-on:destroy="clearInterval(timer)"
                                        >
                                            <span x-text="countdown"></span>
                                        </div>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$site->status === 'active' ? 'green' : 'red'">
                                        {{ ucfirst($site->status) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                        
                                        <flux:menu>
                                            <flux:menu.item icon="arrow-top-right-on-square" href="/s/{{ $site->slug }}" target="_blank">View Site</flux:menu.item>
                                            <!-- Preparing for file editor & domains feature -->
                                            <flux:menu.item icon="code-bracket" :href="route('dashboard.sites.edit', $site->id)" wire:navigate>Edit Files</flux:menu.item>
                                            <flux:menu.item icon="globe-alt" :href="route('dashboard.sites.domains', $site->id)" wire:navigate>Manage Domains</flux:menu.item>
                                            
                                            <flux:menu.separator />
                                            
                                            <flux:menu.submenu icon="clock">
                                                <flux:menu.heading>Extend Expiry</flux:menu.heading>
                                                <flux:menu.item wire:click="extendExpiry({{ $site->id }}, 7)">Add 7 Days</flux:menu.item>
                                                <flux:menu.item wire:click="extendExpiry({{ $site->id }}, 30)">Add 30 Days</flux:menu.item>
                                                <flux:menu.item wire:click="$dispatch('toast', { text: 'Premium feature: Permanent sites' })" class="text-purple-600">Make Permanent</flux:menu.item>
                                            </flux:menu.submenu>

                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" wire:click="deleteSite({{ $site->id }})" wire:confirm="Are you sure you want to delete this site? This action cannot be undone." class="text-red-600">Delete</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </flux:card>
</div>