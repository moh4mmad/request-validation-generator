<?php

namespace RequestValidationGenerator;

use Illuminate\Support\ServiceProvider;

class RequestValidationGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the command
        $this->commands([
            Console\Commands\GenerateRequestValidationsCommand::class,
        ]);
    }

    public function boot()
    {
        // Publish the command to the Laravel application
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Console/Commands/GenerateRequestValidationsCommand.php' => app_path('Console/Commands/GenerateRequestValidationsCommand.php'),
            ], 'request-validations');
        }
    }
}
