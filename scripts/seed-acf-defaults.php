<?php

/**
 * Seed ACF fields with sensible default values so the client sees populated,
 * editable content in wp-admin instead of empty fields (and the composers'
 * fromAcf() path renders real data rather than the hardcoded fallback).
 *
 *   make wp ARGS="eval-file /var/app/scripts/seed-acf-defaults.php"
 *
 * Idempotent. Values mirror the composers' hardcoded fallbacks. Per-language
 * groups are seeded for both PL and EN. Run on local, then mirror to staging via
 * `make push-db-staging` (+ `make sync-uploads-staging` for the seeded images).
 */

if (! defined('ABSPATH') || ! function_exists('update_field')) {
    WP_CLI::error('ACF (update_field) unavailable — run inside the app with ACF active.');
}

/** Import a bundled theme image into the media library (idempotent by source path). */
function seed_import_image(string $themeRelPath, string $title): int
{
    $existing = get_posts([
        'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_key' => '_seed_acf_source', 'meta_value' => $themeRelPath, 'suppress_filters' => false,
    ]);
    if ($existing) {
        return (int) $existing[0];
    }

    $src = get_theme_file_path($themeRelPath);
    if (! file_exists($src)) {
        WP_CLI::warning("brak pliku obrazu: {$src}");

        return 0;
    }

    require_once ABSPATH.'wp-admin/includes/image.php';

    $upload = wp_upload_bits(basename($src), null, file_get_contents($src));
    if (! empty($upload['error'])) {
        WP_CLI::warning($upload['error']);

        return 0;
    }

    $id = wp_insert_attachment([
        'post_mime_type' => wp_check_filetype($upload['file'])['type'],
        'post_title' => $title,
        'post_status' => 'inherit',
    ], $upload['file']);
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
    update_post_meta($id, '_seed_acf_source', $themeRelPath);
    update_post_meta($id, '_wp_attachment_image_alt', $title);

    return (int) $id;
}

// ---- Theme settings (footer) — shared across languages -------------------------
$company = [
    'name' => 'ARPI & Partners Sp. z o.o.',
    'krs' => '0000255170',
    'nip' => '5213382100',
    'address' => "Puławska 182\n02-670 Warszawa",
];

$offices = [
    [
        'name' => 'Warszawa', 'address' => "Puławska 182\n02-670",
        'maps_url' => 'https://maps.app.goo.gl/FyvPhPEQowJeBnW26',
        'phone' => '+48 22 559 00 55', 'email' => 'contact@arpiaccounting.com',
        'hours' => 'pon.–pt., 8:00–16:00',
    ],
    [
        'name' => 'Rzeszów', 'address' => "Juliusza Słowackiego 6/12\n35-060",
        'maps_url' => 'https://maps.app.goo.gl/C792GtwKiCwuzbPc8',
        'phone' => '+48 538 235 852', 'email' => 'contact@arpiaccounting.com',
        'hours' => 'pon.–pt., 8:00–16:00',
    ],
];

$socials = [
    ['network' => 'Facebook', 'url' => 'https://www.facebook.com/arpiaccounting/', 'icon_name' => 'facebook'],
    ['network' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/arpi-accounting/', 'icon_name' => 'linkedin'],
];

$diament = seed_import_image('resources/images/forbes-diament.png', 'Diamenty Forbes 2026');
$laureat = seed_import_image('resources/images/forbes-laureat.png', 'Forbes Laureat 2026');
$badges = array_values(array_filter([
    $diament ? ['image' => $diament, 'alt' => 'Diamenty Forbes 2026'] : null,
    $laureat ? ['image' => $laureat, 'alt' => 'Forbes Laureat 2026'] : null,
]));

$newsletter = [
    'pl' => ['heading' => 'Newsletter', 'submit' => 'Subskrybuj',
        'copy' => 'Zapisz się do naszego newslettera i bądź na bieżąco z najważniejszymi zmianami w polskim prawie'],
    'en' => ['heading' => 'Newsletter', 'submit' => 'Subscribe',
        'copy' => 'Sign up for our newsletter and stay up to date with the most important changes in Polish law'],
];

foreach (['pl', 'en'] as $lang) {
    $id = 'theme_settings_'.$lang;
    update_field('field_ts_company', $company, $id);
    update_field('field_ts_offices', $offices, $id);
    update_field('field_ts_socials', $socials, $id);
    update_field('field_ts_badges', $badges, $id);
    update_field('field_ts_newsletter', $newsletter[$lang], $id);
    WP_CLI::log("✓ theme-settings [{$lang}] — company/offices/socials/badges(".count($badges).")/newsletter");
}

// ---- DBiP options page (language-neutral) --------------------------------------
update_field('dbip_version', 'v 1.25', 'option');
update_field('dbip_date', 'January 2025', 'option');
WP_CLI::log('✓ dbip-settings — version/date');

WP_CLI::success('ACF defaults seeded (theme-settings / footer + dbip options).');
