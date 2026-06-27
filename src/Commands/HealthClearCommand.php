<?php

namespace JTKalkman\LaravelHealth\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class HealthClearCommand extends Command
{
    protected $signature = 'health:clear';

    protected $description = 'Clear the cached health check results';

    public function handle(): int
    {
        try {
            Cache::forget('health::results');
            $this->info('Health check cache cleared.');
        } catch (\Throwable $th) {
            $this->error('Failed to clear health check cache: ' . $th->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
