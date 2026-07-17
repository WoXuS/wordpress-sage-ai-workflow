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

/**
 * Resolve the %chapter-name% token in `dbip-chapters` permalinks.
 *
 * The CPT registers a custom permalink base of `dbip-chapters/%chapter-name%`
 * (resources/acf-json/post_type_…json). WordPress rewrite rules already route
 * the structured URL, but get_permalink() leaves the taxonomy token literal, so
 * substitute the chapter's assigned `chapter-name` term slug (falling back to
 * the Yoast primary term, then the first term, then `uncategorized`).
 */
add_filter('post_type_link', function ($url, $post) {
    if ($post->post_type !== 'dbip-chapters' || strpos($url, '%chapter-name%') === false) {
        return $url;
    }

    $slug  = 'uncategorized';
    $terms = get_the_terms($post->ID, 'chapter-name');
    if ($terms && ! is_wp_error($terms)) {
        $primary = (int) get_post_meta($post->ID, '_yoast_wpseo_primary_chapter-name', true);
        $term = $primary
            ? (current(array_filter($terms, fn ($t) => $t->term_id === $primary)) ?: $terms[0])
            : $terms[0];
        $slug = $term->slug;
    }

    return str_replace('%chapter-name%', $slug, $url);
}, 10, 2);

/**
 * Keep non-production environments (e.g. the cyberFolks staging subdomain) out of
 * search indexes. Gated on WP_ENV via wp_get_environment_type(); production is
 * untouched. Basic Auth on staging is handled at the hosting level.
 */
add_filter('wp_robots', function ($robots) {
    if (wp_get_environment_type() !== 'production') {
        return wp_robots_no_robots($robots);
    }
    return $robots;
});
