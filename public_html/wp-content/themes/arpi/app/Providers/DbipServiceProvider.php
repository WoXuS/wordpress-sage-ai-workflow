<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DbipServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_action('acf/init', function () {
            if (! function_exists('acf_add_options_page')) {
                return;
            }

            acf_add_options_page([
                'page_title' => 'Doing Business in Poland',
                'menu_title' => 'Ustawienia DBiP',
                'menu_slug' => 'dbip-settings',
                'parent_slug' => AdminMenuServiceProvider::SLUG,
                'capability' => 'manage_options',
                'update_button' => 'Zapisz',
            ]);
        });

        // DBiP is English-only — drop WordPress's Polish "Zabezpieczone:" prefix
        // on protected chapter titles (we show our own English notice instead).
        add_filter('protected_title_format', function ($format, $post) {
            return ($post->post_type ?? '') === 'dbip-chapters' ? '%s' : $format;
        }, 10, 2);
    }
}
