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
 * Utility classes for primary-navigation links. The menu is rendered once and
 * restyled by breakpoint: white on the mobile overlay (red bg), red on the
 * desktop bar (white bg).
 */
add_filter('nav_menu_link_attributes', function ($atts, $item, $args) {
    if (($args->theme_location ?? '') !== 'primary_navigation') {
        return $atts;
    }
    $atts['class'] = trim(($atts['class'] ?? '')
        . ' c-btn c-btn--ghost uppercase max-lg:text-white max-lg:hover:text-white/70');
    return $atts;
}, 10, 3);
