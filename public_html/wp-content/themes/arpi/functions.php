<?php

use App\Providers\AcfServiceProvider;
use App\Providers\AdminMenuServiceProvider;
use App\Providers\ContactServiceProvider;
use App\Providers\DbipServiceProvider;
use App\Providers\HomeServiceProvider;
use App\Providers\ThemeServiceProvider;
use App\Providers\ThemeSettingsServiceProvider;
use App\Providers\WhistleblowerServiceProvider;
use Roots\Acorn\Application;

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

Application::configure()
    ->withProviders([
        ThemeServiceProvider::class,
        AdminMenuServiceProvider::class,
        AcfServiceProvider::class,
        DbipServiceProvider::class,
        HomeServiceProvider::class,
        ContactServiceProvider::class,
        ThemeSettingsServiceProvider::class,
        WhistleblowerServiceProvider::class,
    ])
    ->boot();

collect(['setup', 'filters'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });
