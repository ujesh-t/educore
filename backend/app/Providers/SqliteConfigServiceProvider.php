<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class SqliteConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (config('database.default') === 'sqlite' && env('SQLITE_WAL_MODE', false)) {
            try {
                DB::statement('PRAGMA journal_mode=WAL');
                DB::statement('PRAGMA synchronous=NORMAL');
                DB::statement('PRAGMA cache_size=10000');
                DB::statement('PRAGMA temp_store=memory');
                DB::statement('PRAGMA foreign_keys=ON');
            } catch (\Exception $e) {
                \Log::warning('SQLite WAL mode configuration failed: ' . $e->getMessage());
            }
        }
    }
}
