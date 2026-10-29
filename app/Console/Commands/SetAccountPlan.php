<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAccountPlan extends Command
{
    protected $signature = 'accounts:set-plan {email : Account email} {plan : free or paid}';

    protected $description = 'Assign the server-managed account plan (billing is managed externally)';

    public function handle(): int
    {
        $plan = $this->argument('plan');
        if (! in_array($plan, ['free', 'paid'], true)) {
            $this->error('Plan must be free or paid.');

            return self::FAILURE;
        }
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Account not found.');

            return self::FAILURE;
        }
        $user->forceFill(['plan' => $plan])->save();
        $this->info("Plan set to {$plan} for {$user->email}.");

        return self::SUCCESS;
    }
}
