<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Access\AccessController;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();
        $this->routes(function () {
            Route::prefix('/api')
                ->group(function () {
                    Route::get('/version', function () {
                        return env("APP_APIVERSION");
                    });

                    Route::prefix('/access')
                        ->group(base_path('routes/api/access.php'));

                    Route::prefix('/credential')
                        ->group(base_path('routes/api/credential.php'));

                    Route::prefix('/tag')
                        ->group(base_path('routes/api/tag.php'));
                });

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
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
