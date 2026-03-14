<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with('user', auth()->user());
            try {
                if (class_exists(\App\Models\BusinessSetting::class) && \Illuminate\Support\Facades\Schema::hasTable('business_settings')) {
                    $view->with('business', \App\Models\BusinessSetting::get());
                } else {
                    $view->with('business', null);
                }
            } catch (\Throwable $e) {
                $view->with('business', null);
            }
        });
    }
}
