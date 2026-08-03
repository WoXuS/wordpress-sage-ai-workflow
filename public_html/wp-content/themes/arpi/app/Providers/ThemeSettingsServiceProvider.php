<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ThemeSettingsServiceProvider extends ServiceProvider
{
    /**
     * Base ACF post_id for the global theme settings (footer, offices, socials…).
     * The validate_post_id filter below appends the current Polylang language so
     * PL/EN each store their own values under `theme_settings_{lang}` — read with
     * get_field('…', ThemeSettingsServiceProvider::OPTIONS_ID).
     */
    public const OPTIONS_ID = 'theme_settings';

    public function boot(): void
    {
        add_action('acf/init', function () {
            if (! function_exists('acf_add_options_page')) {
                return;
            }

            acf_add_options_page([
                'page_title'    => 'Ustawienia motywu',
                'menu_title'    => 'Ustawienia motywu',
                'menu_slug'     => 'theme-settings',
                'capability'    => 'manage_options',
                'post_id'       => self::OPTIONS_ID,
                'icon_url'      => 'dashicons-admin-generic',
                'position'      => 59,
                'update_button' => 'Zapisz',
                'redirect'      => false,
            ]);
        });

        // Make the theme-settings options page per-language. Scoped strictly to
        // OPTIONS_ID so the DBiP options page (language-neutral 'options') and any
        // post/term ids are untouched. In wp-admin, pll_current_language() follows
        // the Polylang language switcher in the toolbar; on the front end it is the
        // page language. Falls back to the default language when Polylang can't
        // resolve one (e.g. plugin inactive → single shared set of values).
        add_filter('acf/validate_post_id', function ($post_id, $original) {
            if ($original !== self::OPTIONS_ID) {
                return $post_id;
            }

            $lang = function_exists('pll_current_language') ? pll_current_language() : null;

            if (! $lang && function_exists('pll_default_language')) {
                $lang = pll_default_language();
            }

            return $lang ? self::OPTIONS_ID.'_'.$lang : $post_id;
        }, 10, 2);
    }
}
