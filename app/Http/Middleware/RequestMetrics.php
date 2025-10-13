<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Prometheus\Facades\Prometheus;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestMetrics
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is(config('prometheus.urls.default', 'prometheus')) || $request->is('health')) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $method = strtolower($request->getMethod());
        $routeLabel = $this->resolveRouteLabel($request);

        try {
            /** @var \Symfony\Component\HttpFoundation\Response $response */
            $response = $next($request);
        } catch (Throwable $throwable) {
            $this->recordMetrics($method, $routeLabel, 500, microtime(true) - $startedAt);

            throw $throwable;
        }

        $this->recordMetrics($method, $routeLabel, $response->getStatusCode(), microtime(true) - $startedAt);

        return $response;
    }

    protected function recordMetrics(string $method, string $route, int $status, float $duration): void
    {
        Prometheus::addCounter('hotel_http_requests_total')
            ->helpText('Total number of HTTP requests received')
            ->labels(['method', 'route', 'status'])
            ->inc(1, [$method, $route, (string) $status]);

        Prometheus::addCounter('hotel_http_request_duration_seconds_sum')
            ->helpText('Sum of request durations in seconds')
            ->labels(['method', 'route'])
            ->inc($duration, [$method, $route]);

        Prometheus::addCounter('hotel_http_request_duration_seconds_count')
            ->helpText('Count of requests recorded for duration tracking')
            ->labels(['method', 'route'])
            ->inc(1, [$method, $route]);

        foreach ([0.05, 0.1, 0.25, 0.5, 1.0, 2.0, 5.0, 10.0] as $bucket) {
            if ($duration <= $bucket) {
                Prometheus::addCounter('hotel_http_request_duration_seconds_bucket')
                    ->helpText('Request duration buckets')
                    ->labels(['method', 'route', 'le'])
                    ->inc(1, [$method, $route, (string) $bucket]);
            }
        }

        Prometheus::addCounter('hotel_http_request_duration_seconds_bucket')
            ->helpText('Request duration buckets')
            ->labels(['method', 'route', 'le'])
            ->inc(1, [$method, $route, '+Inf']);

        if ($status >= 500) {
            Prometheus::addCounter('hotel_http_requests_error_total')
                ->helpText('Total number of HTTP errors (5xx)')
                ->labels(['route'])
                ->inc(1, [$route]);
        }
    }

    protected function resolveRouteLabel(Request $request): string
    {
        $route = optional($request->route())->getName();

        if (! empty($route)) {
            return Str::slug($route, '.');
        }

        $uri = optional($request->route())->uri();
        if ($uri) {
            return Str::slug($uri, '.');
        }

        $path = $request->path();

        return $path === '/' ? 'root' : Str::slug($path, '.');
    }
}
