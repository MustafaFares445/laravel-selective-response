<?php

namespace MustafaFares\SelectiveResponse;

use Illuminate\Support\ServiceProvider;
use MustafaFares\SelectiveResponse\Extensions\SelectiveResponseExtension;

class SelectiveResponseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/selective-response.php' => config_path('selective-response.php'),
        ], 'selective-response-config');

        $this->registerScrambleExtension();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/selective-response.php',
            'selective-response'
        );
    }

    protected function registerScrambleExtension(): void
    {
        if (!$this->isScrambleInstalled()) {
            return;
        }

        if (!config('selective-response.scramble.enabled', true)) {
            return;
        }

        // Try to register extension if Scramble config exists
        if (file_exists(config_path('scramble.php'))) {
            $scrambleConfig = config('scramble', []);
            $extensions = $scrambleConfig['extensions'] ?? [];

            if (!in_array(SelectiveResponseExtension::class, $extensions)) {
                // Note: Users need to manually add the extension to config/scramble.php
                // This is documented in the README
            }
        }
    }

    protected function isScrambleInstalled(): bool
    {
        return class_exists(\Dedoc\Scramble\Scramble::class);
    }
}

