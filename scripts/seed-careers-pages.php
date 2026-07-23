<?php
/**
 * Seed the Careers pages (PL "Kariera" + EN "Careers") for the arpi theme.
 *
 * Idempotent: re-running finds the pages by slug (or creates them), assigns the
 * `template-careers.blade.php` template, and links them as Polylang translations.
 * Safe on a fresh database.
 *
 * Run:  make wp ARGS="eval-file /var/app/scripts/seed-careers-pages.php"
 */

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Must run through WP-CLI (wp eval-file).\n");
    exit(1);
}

$template = 'template-careers.blade.php';

$pages = [
    'pl' => ['title' => 'Kariera', 'slug' => 'kariera'],
    'en' => ['title' => 'Careers', 'slug' => 'careers'],
];

$ids = [];

foreach ($pages as $lang => $def) {
    $existing = get_page_by_path($def['slug']);
    $id = $existing ? (int) $existing->ID : (int) wp_insert_post([
        'post_type'   => 'page',
        'post_title'  => $def['title'],
        'post_name'   => $def['slug'],
        'post_status' => 'publish',
    ]);

    if (is_wp_error($id) || ! $id) {
        WP_CLI::error("Could not create/find page '{$def['title']}'.");
    }

    update_post_meta($id, '_wp_page_template', $template);

    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($id, $lang);
    }

    $ids[$lang] = $id;
    WP_CLI::log("Page '{$def['title']}' (#{$id}) → template {$template}, lang {$lang}.");
}

// Link PL <-> EN as translations of each other.
if (function_exists('pll_save_post_translations')) {
    pll_save_post_translations($ids);
    WP_CLI::log('Polylang translations linked: ' . json_encode($ids));
}

WP_CLI::success('Careers pages seeded (PL + EN).');
