<x-layouts::app :title="__('AI Help')">
    <div class="mx-auto w-full max-w-2xl space-y-6 p-6">
        <flux:heading size="xl">AI Help</flux:heading>
        <flux:text>Powered by Grok. 1 request per minute, 10 per day (UTC). Shared daily capacity is limited. Messages and tool attempts are logged. Your message and requested site details are sent to xAI. Answers may be inaccurate.</flux:text>
        @if(session('assistant_answer'))
            <div class="whitespace-pre-wrap rounded-xl border p-4" role="status">{{ session('assistant_answer') }}</div>
        @endif
        <form method="POST" action="{{ route('dashboard.help.ask') }}" class="space-y-4">
            @csrf
            <flux:textarea name="message" label="How can we help?" maxlength="2000" required>{{ old('message') }}</flux:textarea>
            <flux:checkbox name="allow_changes" value="1" label="Allow renaming my sites for this request" />
            <flux:button type="submit" variant="primary">Ask Grok</flux:button>
        </form>
    </div>
</x-layouts::app>
