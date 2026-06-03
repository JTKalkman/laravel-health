<?php

namespace JTKalkman\LaravelHealth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use JTKalkman\LaravelHealth\HealthCheckStatus;

final class HealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $ttl = config('health.cache_ttl', 30);

        try {
            $payload = $ttl > 0
                ? Cache::remember('health::results', $ttl, fn() => $this->runChecks())
                : $this->runChecks();
        } catch (\Throwable $th) {
            logger()->warning(
                'Health check: configured cache store unavailable, falling back to file cache. ' .
                'If using database cache driver, the database may be down. Error: ' . $th->getMessage()
            );

            try {
                $payload = $ttl > 0
                    ? Cache::store('file')->remember('health::results', $ttl, fn() => $this->runChecks())
                    : $this->runChecks();
            } catch (\Throwable $th) {
                logger()->error(
                    'Health check: file cache also unavailable, running checks uncached. ' .
                    'Server may be under increased load. Error: ' . $th->getMessage()
                );
                $payload = $this->runChecks();
            }
        }

        $httpStatus = $payload['status'] === HealthCheckStatus::OK->value ? 200 : 503;

        return response()->json($payload, $httpStatus);
    }

    private function runChecks(): array
    {
        $checks = config('health.checks', []);
        $results = [];
        $worstStatus = HealthCheckStatus::OK;

        foreach ($checks as $check) {
            $result = is_callable($check)
                ? $check()->run()
                : $check->run();

            $results[] = array_filter([
                'name'        => $result->name,
                'description' => $result->description,
                'value'       => $result->value,
                'status'      => $result->status,
            ], fn ($value) => $value !== null);

            $worstStatus = $this->resolveWorstStatus($worstStatus, $result->status);
        }

        return [
            'results' => $results,
            'status'  => $worstStatus->value,
        ];
    }

    private function resolveWorstStatus(HealthCheckStatus $current, string $new): HealthCheckStatus
    {
        $priority = [
            HealthCheckStatus::OK->value     => 0,
            HealthCheckStatus::WARNING->value => 1,
            HealthCheckStatus::ERROR->value   => 2,
        ];

        $newStatus = HealthCheckStatus::from($new);

        return $priority[$newStatus->value] > $priority[$current->value]
            ? $newStatus
            : $current;
    }
}
