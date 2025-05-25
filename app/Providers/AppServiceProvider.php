<?php

namespace App\Providers;

use App\Services\BulkInscripcionService;
use App\Services\CategoriaService;
use App\Services\InscripcionQueryService;
use App\Services\InscripcionService;
use App\Services\PostulanteService;
use App\Services\OlimpiadaService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OlimpiadaService::class, function ($app) {
            return new OlimpiadaService();
        });

        $this->app->singleton(InscripcionService::class, function ($app) {
            return new InscripcionService();
        });

        $this->app->singleton(InscripcionQueryService::class, function ($app) {
            return new InscripcionQueryService();
        });

        $this->app->singleton(PostulanteService::class, function ($app) {
            return new PostulanteService();
        });

        $this->app->singleton(CategoriaService::class, function ($app) {
            return new CategoriaService();
        });

        $this->app->singleton(BulkInscripcionService::class, function ($app) {
            return new BulkInscripcionService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
