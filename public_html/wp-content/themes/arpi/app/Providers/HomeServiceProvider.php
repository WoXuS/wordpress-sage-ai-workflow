<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HomeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ACF's "Page Type = Front Page" rule only matches the post stored in
        // `page_on_front` (the Polish page). Polylang keeps the English home as a
        // separate, linked translation, so without this the Home field group would
        // be missing when editing the EN page. Treat any translation of the front
        // page as a front page too — keeps the location rule env-agnostic (no
        // hardcoded post IDs in the versioned field group).
        add_filter('acf/location/match_rule', function ($result, $rule, $screen) {
            if (($rule['param'] ?? '') !== 'page_type' || ($rule['value'] ?? '') !== 'front_page') {
                return $result;
            }

            if ($result || empty($screen['post_id']) || ! function_exists('pll_get_post')) {
                return $result;
            }

            $front = (int) get_option('page_on_front');

            return $front && (int) pll_get_post($front, pll_get_post_language((int) $screen['post_id'])) === (int) $screen['post_id'];
        }, 10, 3);
    }
}
