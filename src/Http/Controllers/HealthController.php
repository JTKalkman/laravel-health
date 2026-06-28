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
        $payload = null;

        if ($ttl > 0) {
            try {
                $payload = Cache::get('health::results');
            } catch (\Throwable $th) {
                logger()->warning(
                    'Health check: cache unavailable, running checks uncached. ' .
                    'Error: ' . $th->getMessage()
                );
            }
        }

        if ($payload === null) {
            $payload = $this->runChecks();

            if ($ttl > 0) {
                try {
                    Cache::put('health::results', $payload, $ttl);
                } catch (\Throwable $th) {
                    logger()->warning(
                        'Health check: cache write failed. ' .
                        'Error: ' . $th->getMessage()
                    );
                }
            }
        }

        $httpStatus = $payload['status'] === HealthCheckStatus::OK ? 200 : 503;

        return response()->json($payload, $httpStatus);
    }

    private function runChecks(): array
    {
        $checks = config('health.checks', []);
        $results = [];
        $names = [];
        $status = HealthCheckStatus::OK;

        foreach ($checks as [$class, $params]) {
            $instance = new $class(...$params);

            if (in_array($instance->name(), $names)) {
                logger()->warning(
                    "Health check: duplicate check name '{$instance->name()}' detected. " .
                    'Check your config/health.php for duplicate entries.'
                );
                continue;
            }

            $names[] = $instance->name();
            $result = $instance->run();

            $results[] = array_filter([
                'name'        => $result->name,
                'description' => $result->description,
                'value'       => $result->value,
                'status'      => $result->status,
            ], fn($value) => $value !== null);

            $status = $status->worst($result->status);
        }

        return [
            'results' => $results,
            'status'  => $status,
        ];
    }
}
