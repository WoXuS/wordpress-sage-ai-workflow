<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Utility classes for primary-navigation links. `arpi_variant` (a custom
 * wp_nav_menu arg) picks the colour set: red on the white desktop bar,
 * white in the mobile overlay.
 */
add_filter('nav_menu_link_attributes', function ($atts, $item, $args) {
    if (($args->theme_location ?? '') !== 'primary_navigation') {
        return $atts;
    }
    $base = 'uppercase tracking-wide text-body-sm transition-opacity hover:opacity-70';
    $colour = (($args->arpi_variant ?? 'desktop') === 'mobile') ? 'text-white' : 'text-red';
    $atts['class'] = trim(($atts['class'] ?? '') . " {$base} {$colour}");
    return $atts;
}, 10, 3);
