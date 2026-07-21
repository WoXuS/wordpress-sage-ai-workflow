<?php

/**
 * Set the "Pozostałe usługi" tile excerpt (PL + EN) and the menu_order that
 * controls the grid order, for every usluga. Order matches the Figma design:
 * Księgowość, Budżetowanie, Spółki, Prawo, Kadry, Doradztwo.
 *
 * Idempotent. Run: make wp ARGS="eval-file /var/app/scripts/seed-usluga-tiles.php"
 */

if (! function_exists('update_field')) {
    WP_CLI::error('ACF not active — cannot seed tile excerpts.');
}

$tiles = [
    ['pl' => 'ksiegowosc', 'en' => 'accounting', 'order' => 1,
        'pl_desc' => 'Świat księgowości nie ma dla nas tajemnic. Rachunkowość prowadzona rzeczowo to realna korzyść dla Twojej firmy.',
        'en_desc' => 'The world of accounting holds no secrets for us. Bookkeeping done properly is a real asset to your company.'],
    ['pl' => 'budzetowanie-i-raporty', 'en' => 'budgeting-and-reporting', 'order' => 2,
        'pl_desc' => 'Nasi specjaliści umożliwią Ci kontrolę nad budżetem firmy poprzez specjalnie przygotowane analizy i raporty.',
        'en_desc' => "Our specialists give you control over your company's budget through purpose-built analyses and reports."],
    ['pl' => 'spolki-handlowe', 'en' => 'commercial-companies', 'order' => 3,
        'pl_desc' => 'Wspierając się międzynarodowym doświadczeniem, oferujemy kompleksową obsługę dla spółek handlowych.',
        'en_desc' => 'Drawing on international experience, we provide comprehensive support for commercial companies.'],
    ['pl' => 'prawo', 'en' => 'law', 'order' => 4,
        'pl_desc' => 'Nasz dział prawny posiada kompetencje i doświadczenie w zakresie prawa krajowego i europejskiego.',
        'en_desc' => 'Our legal team has the competence and experience across both national and European law.'],
    ['pl' => 'kadry-i-place', 'en' => 'payroll-and-hr', 'order' => 5,
        'pl_desc' => 'Nasi pracownicy zapewniają rzetelnie prowadzoną dokumentację kadrowo-płacową i doradztwo w zakresie prawa pracy.',
        'en_desc' => 'Our team keeps your payroll and HR documentation impeccable and advises you on labour law.'],
    ['pl' => 'doradztwo-podatkowe', 'en' => 'tax-advisory', 'order' => 6,
        'pl_desc' => 'Wspólnie opracujemy optymalny plan rachunkowy, który poprowadzi Twoją firmę we właściwym kierunku.',
        'en_desc' => "Together we'll design an optimal accounting plan that steers your company in the right direction."],
];

$find = function (string $slug): ?int {
    $posts = get_posts([
        'post_type' => 'usluga',
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => 1,
        'lang' => '',
    ]);

    return $posts ? $posts[0]->ID : null;
};

foreach ($tiles as $tile) {
    foreach (['pl', 'en'] as $lang) {
        $id = $find($tile[$lang]);

        if (! $id) {
            WP_CLI::warning("Missing post for slug {$tile[$lang]}");
            continue;
        }

        update_field('tile_excerpt', $tile[$lang.'_desc'], $id);
        wp_update_post(['ID' => $id, 'menu_order' => $tile['order']]);
        WP_CLI::log("Tile set: {$id} ({$tile[$lang]}) order={$tile['order']}");
    }
}

WP_CLI::success('Tile excerpts + order set.');
