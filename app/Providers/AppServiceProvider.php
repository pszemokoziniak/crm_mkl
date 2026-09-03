<?php

namespace App\Providers;

use App\Models\ContactWorkDate;
use App\Observers\ContactWorkDateObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Model::unguard();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Zmiany pobytów na budowach trafiają do rejestru dla kadr.
        ContactWorkDate::observe(ContactWorkDateObserver::class);
    }
}
