<?php

/**
 * Seed the remaining five usluga pages (Kadry i płace, Doradztwo podatkowe,
 * Budżetowanie i raporty, Spółki handlowe, Prawo) in PL + EN, with ACF content
 * matching the Figma design, and link each PL/EN pair as Polylang translations.
 *
 * Idempotent: looked up by slug, re-running updates in place.
 * Run: make wp ARGS="eval-file /var/app/scripts/seed-usluga-services.php"
 */

if (! function_exists('update_field')) {
    WP_CLI::error('ACF not active — cannot seed fields.');
}

$services = [
    // ---- Kadry i płace / Payroll & HR ----
    [
        'pl' => [
            'slug' => 'kadry-i-place',
            'title' => 'Kadry i płace',
            'icon' => 'people',
            'lead' => 'Zarządzamy kadrami i płacami ponad 1050 pracowników miesięcznie. Efektywne zarządzanie tym obszarem wymaga doskonałej znajomości Kodeksu pracy — mamy w tym wieloletnie doświadczenie, a nasi specjaliści dopilnują, by dokumentacja prowadzona była rzetelnie i przejrzyście.',
            'scope' => [
                ['grouped-papers', 'dokumentacja związana z US i ZUS'],
                ['list-on-paper', 'listy płac, zestawienia zbiorcze, wynagrodzenia'],
                ['file-stack', 'paski płac, zaświadczenia o zatrudnieniu'],
                ['people', 'bazy danych pracowników'],
                ['representative', 'rejestracja pracowników w ZUS'],
                ['shield', 'wsparcie przy inspekcjach z ZUS, PIP i US'],
            ],
            'body' => ['Tak jak większość ambitnych i utalentowanych kandydatów — z pewnością szukasz idealnego miejsca pracy, które pozwoli Ci na rozwój w zgodzie z Twoimi oczekiwaniami. Każdy z nas potrzebuje odpowiedniego środowiska, by kształtować własną ścieżkę kariery. Chcesz być częścią ambitnego i ukierunkowanego na cel zespołu, z którym szybko odnajdziesz wspólny język — język zaangażowania i energii?'],
        ],
        'en' => [
            'slug' => 'payroll-and-hr',
            'title' => 'Payroll & HR',
            'icon' => 'people',
            'lead' => 'We manage payroll and HR for more than 1,050 employees every month. Doing it well takes an in-depth knowledge of labour law — we have years of experience here, and our specialists make sure every record is kept reliably and transparently.',
            'scope' => [
                ['grouped-papers', 'documentation for the tax office and social insurance (ZUS)'],
                ['list-on-paper', 'payrolls, summary statements, remuneration'],
                ['file-stack', 'payslips, certificates of employment'],
                ['people', 'employee databases'],
                ['representative', 'registering employees with ZUS'],
                ['shield', 'support during ZUS, PIP and tax-office inspections'],
            ],
            'body' => ["Like most ambitious and talented candidates, you are surely looking for the right place to work — one that lets you grow in line with your expectations. Everyone needs the right environment to shape their own career path. Do you want to be part of an ambitious, goal-oriented team you'll quickly find a common language with — the language of commitment and energy?"],
        ],
    ],

    // ---- Doradztwo podatkowe / Tax advisory ----
    [
        'pl' => [
            'slug' => 'doradztwo-podatkowe',
            'title' => 'Doradztwo podatkowe',
            'icon' => 'bulb',
            'lead' => 'Zespół ARPI Accounting stale monitoruje zmiany w prawie podatkowym. Nadzorujemy, doradzamy i weryfikujemy dotychczasowe działania. Optymalizujemy zobowiązania podatkowe poprzez dostosowanie istniejących procedur.',
            'scope_intro' => 'Z przyjemnością naprowadzimy Twój biznes na właściwe tory.',
            'scope' => [
                ['list-with-check', 'weryfikacja ksiąg rachunkowych i polityk rachunkowości'],
                ['list-on-paper', 'sprawozdania finansowe i deklaracje'],
                ['chart-dots', 'ocena efektywności używanych systemów'],
                ['layers', 'optymalny plan kont'],
                ['file', 'doradztwo w zakresie podatku VAT, CIT i PIT oraz cen transferowych'],
                ['handshake', 'opieka podatkowa'],
                ['representative', 'przedstawicielstwo podatkowe'],
            ],
        ],
        'en' => [
            'slug' => 'tax-advisory',
            'title' => 'Tax advisory',
            'icon' => 'bulb',
            'lead' => 'The ARPI Accounting team constantly monitors changes in tax law. We supervise, advise and review your current arrangements, and optimise your tax obligations by adapting existing procedures.',
            'scope_intro' => "We'll gladly steer your business onto the right track.",
            'scope' => [
                ['list-with-check', 'review of accounting books and accounting policies'],
                ['list-on-paper', 'financial statements and tax returns'],
                ['chart-dots', 'assessment of the effectiveness of the systems in use'],
                ['layers', 'an optimal chart of accounts'],
                ['file', 'advisory on VAT, CIT and PIT, and on transfer pricing'],
                ['handshake', 'tax care'],
                ['representative', 'tax representation'],
            ],
        ],
    ],

    // ---- Budżetowanie i raporty / Budgeting & reporting ----
    [
        'pl' => [
            'slug' => 'budzetowanie-i-raporty',
            'title' => 'Budżetowanie i raporty',
            'icon' => 'three-bar-graph',
            'lead' => 'Rozsądne zarządzanie przepływami budżetowymi to podstawa każdego przedsięwzięcia. Nasi specjaliści umożliwią Ci sprawowanie pełnej kontroli nad budżetem firmy. Dzięki współpracy z ARPI podejmowanie decyzji biznesowych kształtujących przyszłość Twojego przedsiębiorstwa stanie się prostsze.',
            'scope' => [
                ['file', 'raporty finansowe i sprawozdania'],
                ['layers', 'analizy potrzeb informacyjnych zarządu'],
                ['zoom-check', 'ocena możliwości wykorzystania istniejących narzędzi'],
                ['sort-ascending-numbers', 'analizy wskaźnikowe, wykonania i odchyleń budżetu'],
                ['linear-graph', 'metody tworzenia i kontroli budżetu'],
            ],
        ],
        'en' => [
            'slug' => 'budgeting-and-reporting',
            'title' => 'Budgeting & reporting',
            'icon' => 'three-bar-graph',
            'lead' => "Sensible management of budget flows is the foundation of every venture. Our specialists give you full control over your company's budget, and working with ARPI makes the business decisions that shape your company's future simpler.",
            'scope' => [
                ['file', 'financial reports and statements'],
                ['layers', "analysis of management's information needs"],
                ['zoom-check', 'assessment of how well existing tools are used'],
                ['sort-ascending-numbers', 'ratio, performance and budget-variance analyses'],
                ['linear-graph', 'methods for building and controlling the budget'],
            ],
        ],
    ],

    // ---- Spółki handlowe / Commercial companies ----
    [
        'pl' => [
            'slug' => 'spolki-handlowe',
            'title' => 'Spółki handlowe',
            'icon' => 'people-and-buildings',
            'lead' => 'Jeśli rozpoczynasz działalność w Polsce, z pewnością potrzebujesz merytorycznego wsparcia. Od 2001 roku aktywnie pomagamy firmom wzmacniać swoją pozycję na rynku krajowym. Jeśli pozycja Twojej spółki jest już mocna i stabilna, proponujemy usługę długofalowego wsparcia. Nasi eksperci pomogą Ci zwiększyć konkurencyjność Twojej firmy.',
            'scope' => [
                ['shield', 'wsparcie dla firm rozpoczynających działalność w Polsce'],
                ['zoom-check', 'weryfikacja wymagań dotyczących rejestracji firmy'],
                ['file-stack', 'pomoc przy kompletowaniu niezbędnych dokumentów'],
                ['hands-over-person', 'opieka w trakcie integracji i przekształceń spółek'],
                ['rotating-arrows', 'zastępstwo dyrektorów i kontrolerów finansowych'],
            ],
        ],
        'en' => [
            'slug' => 'commercial-companies',
            'title' => 'Commercial companies',
            'icon' => 'people-and-buildings',
            'lead' => "If you're starting a business in Poland, you'll certainly need expert support. Since 2001 we have actively helped companies strengthen their position on the domestic market. If your company already holds a strong, stable position, we offer long-term support. Our experts will help you increase your competitiveness.",
            'scope' => [
                ['shield', 'support for companies starting operations in Poland'],
                ['zoom-check', 'verification of company-registration requirements'],
                ['file-stack', 'help gathering the necessary documents'],
                ['hands-over-person', 'support during company integrations and transformations'],
                ['rotating-arrows', 'standing in for directors and financial controllers'],
            ],
        ],
    ],

    // ---- Prawo / Law ----
    [
        'pl' => [
            'slug' => 'prawo',
            'title' => 'Prawo',
            'icon' => 'scales',
            'lead' => 'Od wielu lat świadczymy usługi doradztwa prawnego dla firm działających w Polsce lub w innych państwach członkowskich UE oraz Europejskiego Obszaru Gospodarczego. Orientujemy się doskonale w lokalnym środowisku prawnym.',
            'scope_intro' => 'Wspólnie ustalimy racjonalne wytyczne dla Twojego biznesu.',
            'scope' => [
                ['pen', 'przygotowanie umów i kontraktów'],
                ['directions', 'umowy B2B i z konsumentami, zawieranie porozumień'],
                ['conversation', 'ogólne warunki i świadczenie usług'],
                ['layers', 'ochrona danych i wsparcie w zakresie RODO'],
                ['phone', 'wsparcie outsourcingowe'],
            ],
        ],
        'en' => [
            'slug' => 'law',
            'title' => 'Law',
            'icon' => 'scales',
            'lead' => 'For many years we have provided legal advisory services to companies operating in Poland and in other EU and European Economic Area member states. We know the local legal environment inside out.',
            'scope_intro' => "Together we'll set out sensible guidelines for your business.",
            'scope' => [
                ['pen', 'drafting agreements and contracts'],
                ['directions', 'B2B and consumer contracts, concluding agreements'],
                ['conversation', 'general terms and the provision of services'],
                ['layers', 'data protection and GDPR support'],
                ['phone', 'outsourcing support'],
            ],
        ],
    ],
];

$seed = function (array $data): int {
    $existing = get_posts([
        'post_type' => 'usluga',
        'name' => $data['slug'],
        'post_status' => 'any',
        'numberposts' => 1,
        'lang' => '',
    ]);

    $id = $existing ? $existing[0]->ID : wp_insert_post([
        'post_type' => 'usluga',
        'post_title' => wp_slash($data['title']),
        'post_name' => $data['slug'],
        'post_status' => 'publish',
    ]);

    update_field('hero', [
        'icon_source' => 'library',
        'icon_name' => $data['icon'],
        'lead' => $data['lead'],
    ], $id);

    update_field('scope_intro', $data['scope_intro'] ?? '', $id);

    update_field('scope', array_map(fn ($row) => [
        'icon_source' => 'library',
        'icon_name' => $row[0],
        'label' => $row[1],
    ], $data['scope']), $id);

    update_field('body', isset($data['body'])
        ? implode('', array_map(fn ($p) => '<p>'.$p.'</p>', $data['body']))
        : '', $id);

    update_field('reports', ['heading' => '', 'items' => []], $id);

    return $id;
};

foreach ($services as $service) {
    $ids = [];

    foreach (['pl', 'en'] as $lang) {
        $id = $seed($service[$lang]);

        if (function_exists('pll_set_post_language')) {
            pll_set_post_language($id, $lang);
        }

        $ids[$lang] = $id;
        WP_CLI::log("Seeded {$lang}: post {$id} ({$service[$lang]['slug']})");
    }

    if (function_exists('pll_save_post_translations')) {
        pll_save_post_translations($ids);
    }
}

WP_CLI::success('Seeded '.count($services).' services (PL + EN).');
