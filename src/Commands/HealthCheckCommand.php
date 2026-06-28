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
        $status = HealthCheckStatus::OK;

        foreach ($checks as [$class, $params]) {
            $instance = new $class(...$params);
            $result = $instance->run();

            $status = $status->worst($result->status);

            $rows[] = [
                $this->colorize($result->name, $result->status),
                $result->description ?? '',
                $result->value !== null ? (string) $result->value : '',
                $this->colorize($result->status->value, $result->status),
            ];
        }

        $this->table(
            ['Name', 'Description', 'Value', 'Status'],
            $rows,
        );

        $this->newLine();

        if ($status === HealthCheckStatus::OK) {
            $this->info('Overall status: ok');
            return self::SUCCESS;
        }

        if ($status === HealthCheckStatus::WARNING) {
            $this->warn('Overall status: warning');
            return 1;
        }

        $this->error('Overall status: ' . $status->value);
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
