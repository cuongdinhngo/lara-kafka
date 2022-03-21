<?php

namespace LaraAssistant\LaraKafka;

use Illuminate\Support\ServiceProvider;

class LaraKafkaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Kafka' => app_path('Libraries/Kafka'),
        ], 'app');
    }
}