<x-layouts::app :title="__('AI Help')">
    <div class="mx-auto w-full max-w-3xl space-y-6"
        x-data="{
            message: '', allowChanges: false, busy: false, error: '', answer: @js(session('assistant_answer', '')),
            usage: @js($usage), available: @js($available), now: Math.floor(Date.now() / 1000),
            init() { this.timer = setInterval(() => this.now = Math.floor(Date.now() / 1000), 1000) },
            destroy() { clearInterval(this.timer) },
            get cooldown() { return Math.max(0, this.usage.retry_at - this.now) },
            get blocked() { return !this.available || !this.usage.shared_available || this.usage.remaining === 0 || this.cooldown > 0 },
            async ask() {
                if (this.busy || this.blocked || !this.message.trim()) return;
                this.busy = true; this.error = ''; this.answer = '';
                try {
                    const response = await fetch(@js(route('dashboard.help.ask')), {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                        body: JSON.stringify({ message: this.message, allow_changes: this.allowChanges })
                    });
                    const data = await response.json().catch(() => ({}));
                    if (data.usage) this.usage = data.usage;
                    if (!response.ok) {
                        const messages = { 401: 'Your session ended. Refresh and sign in again.', 419: 'Your session expired. Refresh this page before trying again.', 422: 'Enter a question of up to 2,000 characters.', 429: 'Usage limit reached. Wait for your minute or daily allowance to reset.', 503: 'AI Help is temporarily unavailable.', 502: 'Grok could not complete this request. This attempt counts toward your limit.' };
                        throw new Error(messages[response.status] || 'Unable to complete your request. Refresh to check your remaining allowance.');
                    }
                    this.answer = data.answer;
                } catch (e) {
                    this.error = e instanceof TypeError ? 'Connection lost. Your request may have completed. Check your site before retrying a change.' : e.message;
                } finally { this.busy = false; this.allowChanges = false }
            }
        }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">How can we help?</flux:heading>
                <flux:text class="mt-2">Ask Grok about DropHTML or get help with your sites.</flux:text>
            </div>
            <span class="rounded-full border border-zinc-200 px-3 py-1.5 text-sm dark:border-zinc-700"><span x-text="usage.remaining">{{ $usage['remaining'] }}</span> / 10 requests left today</span>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm">Start with a question</flux:heading>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach(['Show my sites and their status', 'How do I connect a custom domain?', 'How do I fix a missing page?'] as $suggestion)
                    <button type="button" @click="message = @js($suggestion); $refs.question.focus()" :disabled="busy" class="rounded-lg border border-zinc-200 px-3 py-2 text-left text-sm hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ $suggestion }}</button>
                @endforeach
            </div>
        </div>

        <div role="status" class="rounded-xl bg-zinc-100 p-4 text-sm dark:bg-zinc-800" x-show="blocked">
            <template x-if="!available"><p>AI Help is currently unavailable. You can still manage your sites from the dashboard.</p></template>
            <template x-if="available && (!usage.shared_available || usage.remaining === 0)"><p>Today's AI allowance has been reached. Daily limits reset at 00:00 UTC.</p></template>
            <template x-if="available && usage.shared_available && usage.remaining > 0 && cooldown > 0"><p>Ready for another question in <span x-text="cooldown"></span> seconds.</p></template>
        </div>

        <form method="POST" action="{{ route('dashboard.help.ask') }}" @submit.prevent="ask" :aria-busy="busy" class="space-y-4">
            @csrf
            <div>
                <label for="help-question" class="mb-2 block text-sm font-medium">Your question</label>
                <textarea id="help-question" x-ref="question" x-model="message" name="message" maxlength="2000" required rows="5" :disabled="busy" aria-describedby="help-question-hint" class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-sm disabled:opacity-60 dark:border-zinc-700 dark:bg-zinc-900" placeholder="Describe what you need help with…"></textarea>
                <p id="help-question-hint" class="mt-1 text-right text-xs text-zinc-500"><span x-text="message.length">0</span> / 2,000 characters</p>
            </div>
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="allow_changes" value="1" x-model="allowChanges" :disabled="busy" class="mt-1 rounded" /><span>Allow site renaming for this request<span class="mt-1 block text-zinc-500">Off by default. Only your sites can be renamed. Content, domains, deployments, and security settings cannot be changed.</span></span></label>
            </div>
            <flux:button type="submit" variant="primary" x-bind:disabled="busy || blocked || !message.trim()"><span x-text="busy ? 'Asking Grok…' : 'Ask Grok'">Ask Grok</span></flux:button>
            <p x-show="busy" x-cloak role="status" class="text-sm text-zinc-500">Checking your question. Keep this page open to see the result.</p>
            <p x-show="error" x-cloak x-text="error" role="alert" class="rounded-xl border border-red-300 p-4 text-sm text-red-700 dark:text-red-300"></p>
        </form>

        <section x-show="answer" x-cloak aria-live="polite" class="space-y-3 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="sm">Grok's answer</flux:heading>
            <div x-text="answer" class="whitespace-pre-wrap break-words text-sm leading-relaxed"></div>
            <flux:text size="sm">AI answers can be inaccurate. Verify important changes in your dashboard.</flux:text>
        </section>
        <p class="text-xs leading-relaxed text-zinc-500">Each question starts fresh. 1 request per minute, 10 per day (UTC), with shared daily capacity. Failed provider attempts count too. Messages and requested site details are sent to xAI; requests, answers, and tool attempts are logged securely.</p>
        <noscript><p>Enable JavaScript to use AI Help.</p></noscript>
    </div>
</x-layouts::app>
