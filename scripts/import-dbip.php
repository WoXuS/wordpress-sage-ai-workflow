<?php
/**
 * Import the "Doing Business in Poland" chapters (CPT `dbip-chapters` + taxonomy
 * `chapter-name`) from the legacy production database into the fresh arpi DB.
 *
 * The CPT + taxonomy themselves are registered from theme local JSON
 * (resources/acf-json/), so this script only moves CONTENT: the 23 chapter
 * posts, the 5 chapter-name terms, and their relationships. No media, no ACF
 * options pages (ACF Pro), no Polylang (the publication is English-only).
 *
 * Source: legacy DB served by docker-compose.legacy.yml, reachable from the php
 * container at host.docker.internal:3309 (root/root, db arpi_legacy).
 *
 * Idempotent: every created object is tagged with its legacy id
 * (`_legacy_id` postmeta / `_legacy_term_id` termmeta); re-running skips
 * anything already imported.
 *
 * Run:  make wp ARGS="eval-file /var/app/scripts/import-dbip.php"
 */

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Must run through WP-CLI (wp eval-file).\n");
    exit(1);
}

require __DIR__ . '/lib/legacy-db.php';

$src = arpi_legacy_connect();

if (! post_type_exists('dbip-chapters') || ! taxonomy_exists('chapter-name')) {
    WP_CLI::error('CPT `dbip-chapters` / taxonomy `chapter-name` not registered. '
        . 'Ensure resources/acf-json/ is loaded (app/setup.php acf load_json filter).');
}

/* ---- 1. chapter-name terms (legacy term_id => new term_id) ---- */

$term_map = [];
$res = mysqli_query($src, "
    SELECT t.term_id, t.name, t.slug, tt.parent, tt.description
    FROM wp_terms t
    JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
    WHERE tt.taxonomy = 'chapter-name'
    ORDER BY t.term_id
");
$terms_created = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $legacy_id = (int) $row['term_id'];

    // Idempotent: already imported?
    $existing = get_terms([
        'taxonomy'   => 'chapter-name',
        'hide_empty' => false,
        'meta_key'   => '_legacy_term_id',
        'meta_value' => $legacy_id,
        'fields'     => 'ids',
    ]);
    if (! is_wp_error($existing) && $existing) {
        $term_map[$legacy_id] = (int) $existing[0];
        continue;
    }

    $ins = wp_insert_term(wp_slash($row['name']), 'chapter-name', [
        'slug'        => $row['slug'],
        'description' => wp_slash((string) $row['description']),
    ]);
    if (is_wp_error($ins)) {
        // Fall back to matching an existing term with the same slug.
        $t = get_term_by('slug', $row['slug'], 'chapter-name');
        if (! $t) {
            WP_CLI::error("chapter-name term '{$row['name']}': " . $ins->get_error_message());
        }
        $new_id = (int) $t->term_id;
    } else {
        $new_id = (int) $ins['term_id'];
        $terms_created++;
    }
    update_term_meta($new_id, '_legacy_term_id', $legacy_id);
    $term_map[$legacy_id] = $new_id;
}
WP_CLI::log('chapter-name terms: ' . count($term_map) . ' mapped (' . $terms_created . ' new).');

/* ---- 2. chapter posts ---- */

$res = mysqli_query($src, "
    SELECT ID, post_date, post_date_gmt, post_content, post_title, post_excerpt,
           post_status, post_name, menu_order, comment_status, ping_status,
           post_modified, post_modified_gmt
    FROM wp_posts
    WHERE post_type = 'dbip-chapters'
    ORDER BY menu_order, ID
");

$posts_created = 0;
$posts_skipped = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $legacy_id = (int) $row['ID'];

    // Idempotent: already imported?
    $found = get_posts([
        'post_type'   => 'dbip-chapters',
        'post_status' => 'any',
        'numberposts' => 1,
        'fields'      => 'ids',
        'meta_key'    => '_legacy_id',
        'meta_value'  => $legacy_id,
    ]);
    if ($found) {
        $posts_skipped++;
        continue;
    }

    $new_id = wp_insert_post([
        'post_type'         => 'dbip-chapters',
        'post_status'       => $row['post_status'],
        'post_author'       => 1,
        'post_title'        => wp_slash($row['post_title']),
        'post_content'      => wp_slash($row['post_content']),
        'post_excerpt'      => wp_slash((string) $row['post_excerpt']),
        'post_name'         => $row['post_name'],
        'menu_order'        => (int) $row['menu_order'],
        'post_date'         => $row['post_date'],
        'post_date_gmt'     => $row['post_date_gmt'],
        'post_modified'     => $row['post_modified'],
        'post_modified_gmt' => $row['post_modified_gmt'],
        'comment_status'    => $row['comment_status'],
        'ping_status'       => $row['ping_status'],
    ], true);

    if (is_wp_error($new_id)) {
        WP_CLI::error("chapter '{$row['post_title']}' (#{$legacy_id}): " . $new_id->get_error_message());
    }

    update_post_meta($new_id, '_legacy_id', $legacy_id);

    // chapter-name relationships
    $tr = mysqli_query($src, "
        SELECT tt.term_id
        FROM wp_term_relationships trr
        JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = trr.term_taxonomy_id
        WHERE trr.object_id = {$legacy_id} AND tt.taxonomy = 'chapter-name'
    ");
    $new_terms = [];
    while ($t = mysqli_fetch_assoc($tr)) {
        $old = (int) $t['term_id'];
        if (isset($term_map[$old])) {
            $new_terms[] = $term_map[$old];
        }
    }
    if ($new_terms) {
        wp_set_object_terms($new_id, $new_terms, 'chapter-name', false);
    }

    $posts_created++;
}

WP_CLI::log("chapters: {$posts_created} created, {$posts_skipped} skipped (already imported).");

mysqli_close($src);
WP_CLI::success('DBIP import done.');
