<?php

namespace InnoSoft\AuthCore\UI\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

class PruneAuthDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:prune 
                            {--days=30 : The number of days to retain data} 
                            {--tokens : Prune only expired/revoked tokens} 
                            {--logs : Prune only old audit logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired/revoked Sanctum tokens and old audit logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $pruneTokens = $this->option('tokens');
        $pruneLogs = $this->option('logs');

        // If no specific flag is provided, prune both
        if (!$pruneTokens && !$pruneLogs) {
            $pruneTokens = true;
            $pruneLogs = true;
        }

        $this->info("Starting pruning process (Retention: {$days} days)...");

        if ($pruneTokens) {
            $this->pruneTokens($days);
        }

        if ($pruneLogs) {
            $this->pruneLogs($days);
        }

        $this->info('Pruning completed successfully.');

        return self::SUCCESS;
    }

    /**
     * Prune expired or revoked Sanctum tokens.
     */
    protected function pruneTokens(int $days): void
    {
        $this->info('Cleaning up Sanctum tokens...');

        // Use the configured Sanctum model
        $model = Sanctum::$personalAccessTokenModel;

        // 1. Delete expired tokens (expires_at < now)
        $count = $model::where('expires_at', '<', now())->delete();

        $this->line("  - Deleted {$count} expired tokens.");
    }

    /**
     * Prune old audit logs.
     */
    protected function pruneLogs(int $days): void
    {
        $this->info('Cleaning up Audit Logs...');

        $date = now()->subDays($days);

        // Use configured Activity model if available in config, otherwise default
        $activityModel = config('activitylog.activity_model') ?? Activity::class;

        $count = $activityModel::where('created_at', '<', $date)->delete();

        $this->line("  - Deleted {$count} old audit logs.");
    }
}
