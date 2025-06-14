<?php

namespace App\Providers;

use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Transaction\TransactionRepository;
use App\Services\Contracts\TransactionServiceInterface;
use App\Services\Transaction\TransactionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
