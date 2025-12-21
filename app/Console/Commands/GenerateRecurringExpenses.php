<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateRecurringExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(\App\Services\RecurringExpenseService::class);
        $now = \Carbon\Carbon::now();

        $users = \App\Models\User::all();
        $totalGenerated = 0;

        foreach ($users as $user) {
            $generated = $service->generateTransactionsForMonth(
                $user,
                $now->year,
                $now->month
            );
            $totalGenerated += $generated;
        }

        $this->info("Generated {$totalGenerated} transactions for {$now->format('Y-m')}");
    }
}
