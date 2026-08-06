<?php

namespace App;

add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

// One menu, restyled by breakpoint: white on the mobile overlay, red on the desktop bar.
add_filter('nav_menu_link_attributes', function ($atts, $item, $args) {
    if (($args->theme_location ?? '') !== 'primary_navigation') {
        return $atts;
    }
    $atts['class'] = trim(($atts['class'] ?? '')
        . ' c-btn c-btn--ghost uppercase max-lg:text-white max-lg:hover:text-white/70');
    return $atts;
}, 10, 3);

// get_permalink() leaves the %chapter-name% permalink token literal; sub in the
// chapter's term slug (Yoast primary term → first term → uncategorized).
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

// Keep non-production (staging) out of search indexes; production untouched.
add_filter('wp_robots', function ($robots) {
    if (wp_get_environment_type() !== 'production') {
        return wp_robots_no_robots($robots);
    }
    return $robots;
});
