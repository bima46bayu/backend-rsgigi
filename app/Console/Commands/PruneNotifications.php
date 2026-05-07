<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:prune {--days=30 : The number of days to retain notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old notifications from the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = $this->option('days');
        
        $count = DB::table('notifications')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Successfully pruned {$count} notifications older than {$days} days.");

        return Command::SUCCESS;
    }
}
