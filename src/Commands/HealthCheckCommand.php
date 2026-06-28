<?php

namespace JTKalkman\LaravelHealth\Commands;

use Illuminate\Console\Command;
use JTKalkman\LaravelHealth\HealthCheckStatus;

final class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Run all health checks and display the results';

    public function handle(): int
    {
        $checks = config('health.checks', []);
        $rows = [];
        $worstStatus = HealthCheckStatus::OK;

        foreach ($checks as [$class, $params]) {
            $instance = new $class(...$params);
            $result = $instance->run();

            $status = HealthCheckStatus::from($result->status);
            $worstStatus = $worstStatus->worst($status);

            $rows[] = [
                $this->colorize($result->name, $status),
                $result->description ?? '',
                $result->value !== null ? (string) $result->value : '',
                $this->colorize($result->status, $status),
            ];
        }

        $this->table(
            ['Name', 'Description', 'Value', 'Status'],
            $rows,
        );

        $this->newLine();

        if ($worstStatus === HealthCheckStatus::OK) {
            $this->info('Overall status: ok');
            return self::SUCCESS;
        }

        if ($worstStatus === HealthCheckStatus::WARNING) {
            $this->warn('Overall status: warning');
            return 1;
        }

        $this->error('Overall status: ' . $worstStatus->value);
        return 2;
    }

    private function colorize(string $text, HealthCheckStatus $status): string
    {
        return match ($status) {
            HealthCheckStatus::OK      => "<fg=green>{$text}</>",
            HealthCheckStatus::WARNING => "<fg=yellow>{$text}</>",
            HealthCheckStatus::ERROR   => "<fg=red>{$text}</>",
        };
    }
}
