<?php
/**
 * Import the blog (posts + categories, bilingual via Polylang) from the legacy
 * production database into the fresh arpi DB.
 *
 * Scope: post_type `post` (published + drafts), the `category` taxonomy, the
 * per-post/per-term Polylang language, and PL<->EN translation links. No media
 * (featured images are skipped — the dump has no upload files), no tags (there
 * are none), no post meta (Yoast/editor cruft, and _thumbnail_id would dangle).
 * Authors are collapsed onto the admin user (ID 1).
 *
 * Idempotent via `_legacy_id` postmeta / `_legacy_term_id` termmeta.
 *
 * Requires the legacy DB up (see scripts/lib/legacy-db.php).
 *
 * Run:  make wp ARGS="eval-file /var/app/scripts/import-blog.php"
 */

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Must run through WP-CLI (wp eval-file).\n");
    exit(1);
}

require __DIR__ . '/lib/legacy-db.php';

$src = arpi_legacy_connect();

$default_lang = function_exists('pll_default_language') ? pll_default_language() : 'pl';

function q(mysqli $src, string $sql) {
    $r = mysqli_query($src, $sql);
    if (! $r) {
        WP_CLI::error('legacy query failed: ' . mysqli_error($src));
    }
    return $r;
}

/* ---- language lookup maps (legacy object_id => 'pl'|'en') ---- */

$post_lang = [];
$res = q($src, "
    SELECT tr.object_id AS id, t.slug AS lang
    FROM wp_term_relationships tr
    JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'language'
    JOIN wp_terms t ON t.term_id = tt.term_id
");
while ($r = mysqli_fetch_assoc($res)) {
    $post_lang[(int) $r['id']] = $r['lang'];
}

$term_lang = [];
$res = q($src, "
    SELECT tr.object_id AS id, t.slug AS lang
    FROM wp_term_relationships tr
    JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'term_language'
    JOIN wp_terms t ON t.term_id = tt.term_id
");
while ($r = mysqli_fetch_assoc($res)) {
    $term_lang[(int) $r['id']] = str_replace('pll_', '', $r['lang']); // pll_pl => pl
}

/* ================= 1. CATEGORIES ================= */

$cat_map = []; // legacy term_id => new term_id
$cat_created = 0;

$res = q($src, "
    SELECT t.term_id, t.name, t.slug, tt.description
    FROM wp_terms t
    JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
    WHERE tt.taxonomy = 'category'
    ORDER BY t.term_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $legacy_id = (int) $row['term_id'];

    $existing = get_terms([
        'taxonomy'   => 'category',
        'hide_empty' => false,
        'meta_key'   => '_legacy_term_id',
        'meta_value' => $legacy_id,
        'fields'     => 'ids',
    ]);
    if (! is_wp_error($existing) && $existing) {
        $cat_map[$legacy_id] = (int) $existing[0];
        continue;
    }

    $ins = wp_insert_term(wp_slash($row['name']), 'category', [
        'slug'        => $row['slug'],
        'description' => wp_slash((string) $row['description']),
    ]);
    if (is_wp_error($ins)) {
        // Slug already taken (e.g. the default Uncategorized) -> reuse it.
        $t = get_term_by('slug', $row['slug'], 'category');
        if (! $t) {
            WP_CLI::error("category '{$row['name']}': " . $ins->get_error_message());
        }
        $new_id = (int) $t->term_id;
    } else {
        $new_id = (int) $ins['term_id'];
        $cat_created++;
    }
    update_term_meta($new_id, '_legacy_term_id', $legacy_id);

    if (function_exists('pll_set_term_language')) {
        pll_set_term_language($new_id, $term_lang[$legacy_id] ?? $default_lang);
    }
    $cat_map[$legacy_id] = $new_id;
}
WP_CLI::log('categories: ' . count($cat_map) . ' mapped (' . $cat_created . ' new).');

/* ---- category translation links ---- */

$cat_tr = 0;
$res = q($src, "SELECT description FROM wp_term_taxonomy WHERE taxonomy = 'term_translations'");
while ($row = mysqli_fetch_assoc($res)) {
    $group = @unserialize($row['description']);
    if (! is_array($group)) {
        continue;
    }
    $mapped = [];
    foreach ($group as $lang => $legacy_tid) {
        if (isset($cat_map[(int) $legacy_tid])) {
            $mapped[$lang] = $cat_map[(int) $legacy_tid];
        }
    }
    if (count($mapped) >= 2 && function_exists('pll_save_term_translations')) {
        pll_save_term_translations($mapped);
        $cat_tr++;
    }
}
WP_CLI::log("category translation groups linked: {$cat_tr}.");

/* ================= 2. POSTS ================= */

// Pre-load post->category relationships (legacy post_id => [legacy cat term_id, ...]).
$post_cats = [];
$res = q($src, "
    SELECT tr.object_id AS post_id, tt.term_id AS cat_id
    FROM wp_term_relationships tr
    JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'category'
");
while ($r = mysqli_fetch_assoc($res)) {
    $post_cats[(int) $r['post_id']][] = (int) $r['cat_id'];
}

$post_map = [];
$posts_created = 0;
$posts_skipped = 0;

$res = q($src, "
    SELECT ID, post_date, post_date_gmt, post_content, post_title, post_excerpt,
           post_status, post_name, post_modified, post_modified_gmt,
           comment_status, ping_status
    FROM wp_posts
    WHERE post_type = 'post' AND post_status IN ('publish','draft')
    ORDER BY post_date
");
while ($row = mysqli_fetch_assoc($res)) {
    $legacy_id = (int) $row['ID'];

    $found = get_posts([
        'post_type'   => 'post',
        'post_status' => 'any',
        'numberposts' => 1,
        'fields'      => 'ids',
        'meta_key'    => '_legacy_id',
        'meta_value'  => $legacy_id,
    ]);
    if ($found) {
        $post_map[$legacy_id] = (int) $found[0];
        $posts_skipped++;
        continue;
    }

    $new_id = wp_insert_post([
        'post_type'         => 'post',
        'post_status'       => $row['post_status'],
        'post_author'       => 1,
        'post_title'        => wp_slash($row['post_title']),
        'post_content'      => wp_slash($row['post_content']),
        'post_excerpt'      => wp_slash((string) $row['post_excerpt']),
        'post_name'         => $row['post_name'],
        'post_date'         => $row['post_date'],
        'post_date_gmt'     => $row['post_date_gmt'],
        'post_modified'     => $row['post_modified'],
        'post_modified_gmt' => $row['post_modified_gmt'],
        'comment_status'    => $row['comment_status'],
        'ping_status'       => $row['ping_status'],
    ], true);

    if (is_wp_error($new_id)) {
        WP_CLI::error("post '{$row['post_title']}' (#{$legacy_id}): " . $new_id->get_error_message());
    }

    update_post_meta($new_id, '_legacy_id', $legacy_id);

    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($new_id, $post_lang[$legacy_id] ?? $default_lang);
    }

    // categories (mapped, language-consistent by construction)
    $new_cats = [];
    foreach ($post_cats[$legacy_id] ?? [] as $old_cat) {
        if (isset($cat_map[$old_cat])) {
            $new_cats[] = $cat_map[$old_cat];
        }
    }
    if ($new_cats) {
        wp_set_object_terms($new_id, $new_cats, 'category', false);
    }

    $post_map[$legacy_id] = $new_id;
    $posts_created++;
}
WP_CLI::log("posts: {$posts_created} created, {$posts_skipped} skipped.");

/* ---- post translation links ---- */

$post_tr = 0;
$res = q($src, "SELECT description FROM wp_term_taxonomy WHERE taxonomy = 'post_translations'");
while ($row = mysqli_fetch_assoc($res)) {
    $group = @unserialize($row['description']);
    if (! is_array($group)) {
        continue;
    }
    $mapped = [];
    foreach ($group as $lang => $legacy_pid) {
        if (isset($post_map[(int) $legacy_pid])) {
            $mapped[$lang] = $post_map[(int) $legacy_pid];
        }
    }
    if (count($mapped) >= 2 && function_exists('pll_save_post_translations')) {
        pll_save_post_translations($mapped);
        $post_tr++;
    }
}
WP_CLI::log("post translation groups linked: {$post_tr}.");

mysqli_close($src);
WP_CLI::success('Blog import done.');
