<?php

/**
 * Seed the "Księgowość / Accounting" usluga post in PL + EN with ACF content
 * matching the hardcoded MVP, and link the two as Polylang translations.
 *
 * Idempotent: re-running updates the same posts (looked up by slug).
 * Run: make wp ARGS="eval-file /var/app/scripts/seed-usluga-ksiegowosc.php"
 */

if (! function_exists('update_field')) {
    WP_CLI::error('ACF not active — cannot seed fields.');
}

$content = [
    'pl' => [
        'slug' => 'ksiegowosc',
        'title' => 'Księgowość',
        'hero_lead' => 'Z ARPI Accounting możesz swobodnie decydować o kształcie współpracy, ponieważ specjalizujemy się w szerokim spektrum usług administracyjno-księgowych. Wystarczy, że ustalisz zakresy tematyczne i wskażesz nam czynności, które są dla Ciebie najbardziej istotne.',
        'scope' => [
            ['four-bar-graph', 'ewidencje zdarzeń gospodarczych'],
            ['list-on-paper', 'roczne sprawozdania finansowe'],
            ['list-with-check', 'zestawienia obrotów i sald'],
            ['file', 'deklaracje podatkowe [CIT, VAT]'],
            ['shield', 'ewidencje środków trwałych klienta'],
            ['handshake-percent', 'wsparcie przy kontrolach US i współpraca z audytorami'],
            ['people-and-buildings', 'sprawozdania do głównego urzędu statystycznego i NBP'],
        ],
        'body' => [
            'Jesteśmy na bieżąco z najnowszymi zmianami w regulacjach prawnych i technicznych aspektach księgowości, dzięki czemu zapewniamy obsługę rachunkową na najwyższym poziomie. Kontrola nad poprawnością i aktualnością naszych procedur to codzienny priorytet naszych specjalistów.',
            'Opracowaliśmy własne unikalne narzędzie do obsługi faktur – InFlow – które pozwala nam oferować całkowitą lub częściową obsługę operacji bankowych naszych klientów. Bezpieczny system zapewnia kompletną kontrolę merytoryczną przesłanych i wychodzących dokumentów. Po określeniu częstotliwości i charakteru zleceń, zajmiemy się realizacją oraz przygotujemy dla Ciebie codzienny raport z listą podjętych czynności.',
            'Specjalizujemy się również w podstawowych i zaawansowanych raportach finansowych.',
        ],
        'reports_heading' => 'Poza najważniejszymi raportami, jak Bilans czy Rachunek Zysków i Strat, oferujemy również przygotowanie:',
        'reports_items' => [
            'raportu sald intercompany (na kontach z jednostkami powiązanymi)',
            'raportów oferujących podział kosztów pod względem zmiennym: projekt / rodzaje kosztów / jednostki ponoszące koszty',
            'analizy wyników, porównanie przychodów do kosztów (EBIT w rozbiciu na projekty)',
            'zestawienie wyników miesięcznych',
            'wiekowanie należności',
        ],
    ],
    'en' => [
        'slug' => 'accounting',
        'title' => 'Accounting',
        'hero_lead' => 'With ARPI Accounting you can freely decide how we work together, because we specialise in a broad range of administrative and accounting services. Simply define the scope and tell us which tasks matter most to you.',
        'scope' => [
            ['four-bar-graph', 'records of business events'],
            ['list-on-paper', 'annual financial statements'],
            ['list-with-check', 'trial balances (turnover and balances)'],
            ['file', 'tax returns [CIT, VAT]'],
            ['shield', "client's fixed-asset records"],
            ['handshake-percent', 'support during tax audits and cooperation with auditors'],
            ['people-and-buildings', 'reporting to the Central Statistical Office (GUS) and the NBP'],
        ],
        'body' => [
            'We stay up to date with the latest legal and technical changes in accounting, which lets us keep your bookkeeping at the highest level. Making sure our procedures are correct and current is a daily priority for our specialists.',
            'We have developed our own unique invoicing tool – InFlow – which lets us handle your banking operations fully or in part. The secure system provides complete substantive control over incoming and outgoing documents. Once we agree on the frequency and nature of the tasks, we take care of them and prepare a daily report of the actions taken for you.',
            'We also specialise in both basic and advanced financial reporting.',
        ],
        'reports_heading' => 'Beyond the key reports, such as the Balance Sheet or the Profit and Loss Statement, we also prepare:',
        'reports_items' => [
            'intercompany balance reports (on related-party accounts)',
            'reports breaking costs down by variable: project / cost type / cost centre',
            'performance analyses comparing revenue to costs (EBIT split by project)',
            'monthly performance summaries',
            'receivables ageing',
        ],
    ],
];

$ids = [];

foreach ($content as $lang => $data) {
    $existing = get_posts([
        'post_type' => 'usluga',
        'name' => $data['slug'],
        'post_status' => 'any',
        'numberposts' => 1,
        'lang' => '', // don't let Polylang scope the lookup
    ]);

    $id = $existing ? $existing[0]->ID : wp_insert_post([
        'post_type' => 'usluga',
        'post_title' => wp_slash($data['title']),
        'post_name' => $data['slug'],
        'post_status' => 'publish',
    ]);

    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($id, $lang);
    }

    update_field('hero', [
        'icon_source' => 'library',
        'icon_name' => 'three-papers',
        'lead' => $data['hero_lead'],
    ], $id);

    update_field('scope', array_map(fn ($row) => [
        'icon_source' => 'library',
        'icon_name' => $row[0],
        'label' => $row[1],
    ], $data['scope']), $id);

    update_field('body', implode('', array_map(fn ($p) => '<p>'.$p.'</p>', $data['body'])), $id);

    update_field('reports', [
        'heading' => $data['reports_heading'],
        'items' => array_map(fn ($text) => ['text' => $text], $data['reports_items']),
    ], $id);

    $ids[$lang] = $id;
    WP_CLI::log("Seeded {$lang}: post {$id} ({$data['slug']})");
}

if (function_exists('pll_save_post_translations')) {
    pll_save_post_translations($ids);
    WP_CLI::log('Linked translations: '.json_encode($ids));
}

WP_CLI::success('Done.');
