<?php

namespace App\Providers;

use App\Domain\Fiscal\Contracts\InvoiceAuthorizer;
use App\Domain\Fiscal\Support\ArcaInvoiceAuthorizer;
use App\Domain\Fiscal\Support\FakeInvoiceAuthorizer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InvoiceAuthorizer::class, function ($app) {
            return match ((string) config('fiscal.gateway', 'fake')) {
                'arca' => $app->make(ArcaInvoiceAuthorizer::class),
                default => $app->make(FakeInvoiceAuthorizer::class),
            };
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
