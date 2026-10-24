<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RecurringExpenseService;
use Carbon\Carbon;
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
        $service = app(RecurringExpenseService::class);
        $now = Carbon::now();

        $users = User::all();
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
