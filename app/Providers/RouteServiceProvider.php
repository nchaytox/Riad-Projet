<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Prometheus\Facades\Prometheus;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        $authAttempts = (int) config('security.rate_limits.auth.attempts', 5);
        $authDecay = (int) config('security.rate_limits.auth.decay', 1);

        RateLimiter::for('auth', function (Request $request) use ($authAttempts, $authDecay) {
            return Limit::perMinutes($authDecay, $authAttempts)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) use ($authDecay) {
                    $retryAfter = (int) ($headers['Retry-After'][0] ?? $authDecay * 60);

                    Prometheus::addCounter('hotel_login_throttled_total')
                        ->helpText('Total throttled authentication actions')
                        ->labels(['action'])
                        ->inc(1, ['login']);

                    $exception = ValidationException::withMessages([
                        'email' => trans('auth.throttle', ['seconds' => $retryAfter]),
                    ]);
                    $exception->status = 429;

                    throw $exception;
                });
        });

        $bookingAttempts = (int) config('security.rate_limits.booking.attempts', 20);
        $bookingDecay = (int) config('security.rate_limits.booking.decay', 1);

        RateLimiter::for('booking', function (Request $request) use ($bookingAttempts, $bookingDecay) {
            return Limit::perMinutes($bookingDecay, $bookingAttempts)
                ->by(optional($request->user())->id ?: $request->ip())
                ->response(function (Request $request, array $headers) use ($bookingDecay) {
                    $retryAfter = (int) ($headers['Retry-After'][0] ?? $bookingDecay * 60);

                    Prometheus::addCounter('hotel_booking_rate_limited_total')
                        ->helpText('Total rate-limited booking operations')
                        ->labels(['action'])
                        ->inc(1, ['booking']);

                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Too many booking actions. Please retry later.',
                        ], 429)->withHeaders($headers);
                    }

                    return redirect()->back()
                        ->with('failed', 'Too many booking actions. Please try again in '.$retryAfter.' seconds.')
                        ->withHeaders($headers)
                        ->setStatusCode(429);
                });
        });
    }
}
