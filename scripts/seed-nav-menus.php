<?php
/**
 * Seed the header navigation menus (PL + EN) for the arpi theme.
 *
 * Idempotent: re-running rebuilds both menus from scratch and re-assigns them
 * to the `primary_navigation` location per language via Polylang. Safe to run
 * on a fresh database (e.g. after moving machines / re-importing a clean DB).
 *
 * Run:  make wp ARGS="eval-file /var/app/scripts/seed-nav-menus.php"
 * (the repo is mounted at /var/app inside the php container)
 *
 * Menu items are custom links to the intended pretty URLs. Target pages don't
 * all exist yet — once they do, swap these for real page items in wp-admin.
 */

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Must run through WP-CLI (wp eval-file).\n");
    exit(1);
}

/* ---- 1. Pretty permalinks (required by the project + Polylang directory mode) ---- */

if (get_option('permalink_structure') === '') {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure('/%postname%/');
    update_option('permalink_structure', '/%postname%/');
    $wp_rewrite->flush_rules(true);
    WP_CLI::log('Permalinks: switched to /%postname%/ and flushed.');
} else {
    WP_CLI::log('Permalinks: already pretty (' . get_option('permalink_structure') . ').');
}

/* ---- 2. Menu definitions (labels + intended URLs) ---- */

$menus = [
    'pl' => [
        'name'  => 'Header PL',
        'items' => [
            ['Dlaczego ARPI?', home_url('/dlaczego-arpi/')],
            ['Usługi',         home_url('/uslugi/')],
            ['Blog',           home_url('/blog/')],
            ['Kariera',        home_url('/kariera/')],
            ['Proces',         home_url('/proces/')],
            ['Kontakt',        home_url('/kontakt/')],
        ],
    ],
    'en' => [
        'name'  => 'Header EN',
        'items' => [
            ['Why ARPI?', home_url('/en/why-arpi/')],
            ['Services',  home_url('/en/services/')],
            ['Blog',      home_url('/en/blog/')],
            ['Careers',   home_url('/en/careers/')],
            ['Process',   home_url('/en/process/')],
            ['Contact',   home_url('/en/contact/')],
        ],
    ],
];

/* ---- 3. Build each menu (get-or-create, clear, re-add in order) ---- */

$menu_ids = [];

foreach ($menus as $lang => $def) {
    $menu = wp_get_nav_menu_object($def['name']);
    $menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($def['name']);

    if (is_wp_error($menu_id) || ! $menu_id) {
        WP_CLI::error("Could not create/find menu '{$def['name']}'.");
    }

    // Clear existing items so ordering is deterministic on every run.
    foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
        wp_delete_post($item->ID, true);
    }

    foreach ($def['items'] as $item) {
        [$title, $url] = $item;
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'  => $title,
            'menu-item-url'    => $url,
            'menu-item-type'   => 'custom',
            'menu-item-status' => 'publish',
        ]);
    }

    // Tag the menu with its Polylang language (best-effort; ignored if PLL absent).
    if (function_exists('pll_set_term_language')) {
        pll_set_term_language($menu_id, $lang);
    }

    $menu_ids[$lang] = $menu_id;
    WP_CLI::log("Menu '{$def['name']}' (#{$menu_id}): " . count($def['items']) . ' items.');
}

/* ---- 4. Assign menus to the primary_navigation location per language ---- */

$theme = get_option('stylesheet'); // 'arpi'

// Default (PL) location via theme mods.
$locations = (array) get_theme_mod('nav_menu_locations', []);
$locations['primary_navigation'] = $menu_ids['pl'];
set_theme_mod('nav_menu_locations', $locations);

// Per-language mapping via Polylang.
$pll = get_option('polylang');
if (is_array($pll)) {
    foreach ($menu_ids as $lang => $mid) {
        $pll['nav_menus'][$theme]['primary_navigation'][$lang] = $mid;
    }
    update_option('polylang', $pll);
    WP_CLI::log('Polylang nav_menus: ' . json_encode($pll['nav_menus'][$theme]['primary_navigation']));
}

WP_CLI::success('Header menus seeded (PL + EN).');
