<?php

/**
 * DBiP data seed. Idempotent.
 * Run: make wp ARGS="eval-file /var/app/scripts/seed-dbip.php"
 *
 * Reproduces production state that the content import didn't carry over:
 *  - chapter order (legacy term_order → dbip_chapter_order termmeta)
 *  - per-chapter title image (ACF title_image) + intro (ACF chapter_introduction)
 *  - glossary dedupe + "no-chapter" term (keeps the /no-chapter/glossary/ URL)
 *  - post passwords (17 subchapters, same password as prod)
 *  - version/date options
 */

if (! function_exists('update_field')) {
    WP_CLI::error('ACF not active — cannot seed DBiP.');
}

require_once ABSPATH.'wp-admin/includes/image.php';

$termByLegacy = function (int $legacyId): ?WP_Term {
    $terms = get_terms([
        'taxonomy' => 'chapter-name',
        'hide_empty' => false,
        'meta_key' => '_legacy_term_id',
        'meta_value' => $legacyId,
        'number' => 1,
    ]);

    return ($terms && ! is_wp_error($terms)) ? $terms[0] : null;
};

$sideload = function (string $filename, string $title) {
    $existing = get_posts([
        'post_type' => 'attachment',
        'numberposts' => 1,
        'post_status' => 'inherit',
        'meta_key' => '_dbip_seed_file',
        'meta_value' => $filename,
    ]);
    if ($existing) {
        return $existing[0]->ID;
    }

    $src = '/var/app/scripts/assets/dbip/'.$filename;
    if (! file_exists($src)) {
        WP_CLI::warning("Missing image $src");

        return null;
    }

    $upload = wp_upload_bits($filename, null, file_get_contents($src));
    if (! empty($upload['error'])) {
        WP_CLI::warning($upload['error']);

        return null;
    }

    $attachId = wp_insert_attachment([
        'post_mime_type' => wp_check_filetype($upload['file'])['type'],
        'post_title' => $title,
        'post_status' => 'inherit',
    ], $upload['file']);

    wp_update_attachment_metadata($attachId, wp_generate_attachment_metadata($attachId, $upload['file']));
    update_post_meta($attachId, '_dbip_seed_file', $filename);

    return $attachId;
};

/* 1. Chapter order + title images (legacy term_id => [order, image]) */
$chapters = [
    283 => ['order' => 1, 'image' => 'chapter1-romb.png'],
    287 => ['order' => 2, 'image' => 'chapter2-romb.png'],
    284 => ['order' => 3, 'image' => 'chapter3-romb.png'],
    285 => ['order' => 4, 'image' => 'chapter4-romb.png'],
    286 => ['order' => 5, 'image' => 'chapter5-romb.png'],
];

foreach ($chapters as $legacyId => $data) {
    $term = $termByLegacy($legacyId);
    if (! $term) {
        WP_CLI::warning("No local term for legacy $legacyId");
        continue;
    }

    update_term_meta($term->term_id, 'dbip_chapter_order', $data['order']);

    $attach = $sideload($data['image'], $term->name);
    if ($attach) {
        update_field('title_image', $attach, 'term_'.$term->term_id);
    }

    WP_CLI::log("Chapter «{$term->name}»: order={$data['order']} image=".($attach ?: 'none'));
}

/* 2. Chapter 1 (Types) intro */
$intro = <<<'HTML'
<p>Polish law provides the following types of legal entities for conducting economic activity:</p>
<ul>
<li>Capital companies
<ul>
<li>Limited liability company</li>
<li>Joint-stock company</li>
<li>Simple joint-stock company</li>
</ul>
</li>
<li>Partnerships
<ul>
<li>Registered partnership</li>
<li>Professional partnership</li>
<li>Limited partnership</li>
<li>Limited joint-stock partnership</li>
</ul>
</li>
<li>Sole trader (self-employment)</li>
<li>Branch (foreign entrepreneur)</li>
<li>Representative office (foreign entrepreneur)</li>
</ul>
<p>The choice of the appropriate form of business depends on the specific needs of each enterprise and industry. Therefore it is difficult to objectively indicate the most beneficial one. Below, we present a brief overview of the most popular types of entities, with their advantages and disadvantages.</p>
HTML;

if ($types = $termByLegacy(283)) {
    update_field('chapter_introduction', $intro, 'term_'.$types->term_id);
    WP_CLI::log('Intro set on «Types of business entities».');
}

/* 3. Glossary dedupe + no-chapter term */
$all = get_posts(['post_type' => 'dbip-chapters', 'numberposts' => -1, 'post_status' => 'any']);
$gloss = array_values(array_filter($all, fn ($p) => strcasecmp($p->post_title, 'Glossary') === 0));
usort($gloss, fn ($a, $b) => strlen($b->post_content) <=> strlen($a->post_content));

$keep = $gloss[0] ?? null;
foreach (array_slice($gloss, 1) as $dup) {
    wp_trash_post($dup->ID);
    WP_CLI::log("Trashed duplicate Glossary #{$dup->ID}.");
}

$nc = get_term_by('slug', 'no-chapter', 'chapter-name');
$ncId = $nc ? $nc->term_id : (wp_insert_term('No chapter', 'chapter-name', ['slug' => 'no-chapter'])['term_id'] ?? null);
if ($keep && $ncId) {
    wp_set_object_terms($keep->ID, [(int) $ncId], 'chapter-name', false);
    WP_CLI::log("Glossary #{$keep->ID} → no-chapter term.");
}

/* 4. Post passwords (same as prod) */
$protected = [
    'The minimum wage', 'Legal system', 'VAT', 'Other tax obligations', 'Obtaining NIP',
    'Types of contract', 'Employment of foreginers', 'Social security', 'Employee Capital Plans',
    'Financial statements', 'Inventory of fixed assets & Audit', 'Unified Control File (JPK)',
    'Central Register of Ultimate Beneficial Owners', 'GDPR / AML', 'CIT', 'PIT', 'Tax reliefs (PIT)',
];

// Password is a real credential — supply it out of band, never in the repo:
//   DBIP_PASSWORD=... make wp ARGS="eval-file /var/app/scripts/seed-dbip.php"
$pwd = getenv('DBIP_PASSWORD') ?: '';
if ($pwd === '') {
    WP_CLI::log('DBIP_PASSWORD not set — leaving existing password protection unchanged.');
} else {
    $n = 0;
    foreach ($all as $p) {
        if (in_array($p->post_title, $protected, true) && $p->post_password !== $pwd) {
            wp_update_post(['ID' => $p->ID, 'post_password' => $pwd]);
            $n++;
        }
    }
    WP_CLI::log("Passwords set on {$n} post(s).");
}

/* 4b. Widen tables via the block's own align option (wideSize), so the
   constrained layout doesn't cap them at contentSize. No custom CSS. */
$aligned = 0;
foreach ($all as $p) {
    if (strpos($p->post_content, 'wp:table') === false) {
        continue;
    }

    $new = preg_replace_callback('/<!-- wp:table(\s+(\{.*?\}))? -->/', function ($m) {
        $attrs = isset($m[2]) ? (json_decode($m[2], true) ?: []) : [];
        if (($attrs['align'] ?? '') === 'wide') {
            return $m[0];
        }
        $attrs = ['align' => 'wide'] + $attrs;

        return '<!-- wp:table '.wp_json_encode($attrs).' -->';
    }, $p->post_content);

    $new = preg_replace('/<figure class="wp-block-table(?! alignwide)/', '<figure class="wp-block-table alignwide', $new);

    if ($new !== $p->post_content) {
        wp_update_post(['ID' => $p->ID, 'post_content' => wp_slash($new)]);
        $aligned++;
    }
}
WP_CLI::log("Tables set to align=wide in {$aligned} post(s).");

/* 5. Version / date options */
if (! get_field('dbip_version', 'option')) {
    update_field('dbip_version', 'v 1.25', 'option');
}
if (! get_field('dbip_date', 'option')) {
    update_field('dbip_date', 'January 2025', 'option');
}

WP_CLI::success('DBiP seed complete.');
