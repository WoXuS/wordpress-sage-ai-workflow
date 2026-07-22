<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use Illuminate\Support\Facades\Vite;

class Home extends Composer
{
    protected static $views = ['front-page'];

    public function with(): array
    {
        $content = $this->fromAcf() ?? $this->fromHardcoded();

        return [
            'hero'           => $content['hero'],
            'about'          => $content['about'],
            'memberships'    => $content['memberships'],
            'why'            => $content['why'],
            'services'       => $content['services'],
            'dbip'           => $content['dbip'],
            'blog'           => $this->blogMeta($content['blog'] ?? []),
            'blogCategories' => $this->blogCategories(),
            'latestPosts'    => $this->latestPosts(),
        ];
    }

    // ---- Primary source: ACF (group_home on the front page) ----------------
    // Reads the current-language front page (Polylang serves the PL/EN page per
    // request), so each language edits its own content in wp-admin. Returns null
    // until the hero is filled in — then the hardcoded fallback renders instead.

    private function fromAcf(): ?array
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $id = get_queried_object_id() ?: (int) get_option('page_on_front');
        $hero = get_field('hero', $id);

        if (! $hero || empty($hero['title'])) {
            return null;
        }

        $about = get_field('about', $id) ?: [];
        $memberships = get_field('memberships', $id) ?: [];
        $why = get_field('why', $id) ?: [];
        $services = get_field('services', $id) ?: [];
        $dbip = get_field('dbip', $id) ?: [];

        return [
            'hero' => [
                'title'         => $hero['title'] ?? '',
                'accent'        => $hero['accent'] ?? '',
                'lead'          => $hero['lead'] ?? '',
                'image_desktop' => $this->imageUrl($hero['image_desktop'] ?? null, 'resources/images/hp-hero--desktop.svg'),
                'image_mobile'  => $this->imageUrl($hero['image_mobile'] ?? null, 'resources/images/hp-hero--mobile.svg'),
                'image_alt'     => $hero['image_alt'] ?? '',
            ],
            'about' => [
                'heading'       => $about['heading'] ?? '',
                'lead'          => $about['lead'] ?? '',
                'stats_desktop' => $about['stats_desktop'] ?? '',
                'stats_mobile'  => $about['stats_mobile'] ?? '',
                'image'         => $this->imageUrl($about['image'] ?? null, 'resources/images/about-us.png'),
                'image_alt'     => $about['image_alt'] ?? '',
            ],
            'memberships' => [
                'heading'   => $memberships['heading'] ?? '',
                'lead'      => $memberships['lead'] ?? '',
                'image'     => $this->imageUrl($memberships['image'] ?? null, 'resources/images/companies.png'),
                'image_alt' => $memberships['image_alt'] ?? '',
            ],
            'why' => [
                'heading'   => $why['heading'] ?? '',
                'intro'     => $why['intro'] ?? '',
                'hexes'     => array_map(fn ($row) => $row['label'] ?? '', $why['hexes'] ?? []),
                'statement' => $why['statement'] ?? '',
                'caption'   => $why['caption'] ?? '',
            ],
            'services' => [
                'heading' => $services['heading'] ?? '',
                'items'   => array_map(fn ($row) => [
                    'name' => $row['name'] ?? '',
                    'icon' => $row['icon_name'] ?? 'three-papers',
                    'url'  => is_array($row['link'] ?? null) ? ($row['link']['url'] ?? '#') : '#',
                ], $services['items'] ?? []),
            ],
            'dbip' => [
                'heading'    => $dbip['heading'] ?? '',
                'paragraphs' => array_map(fn ($row) => $row['text'] ?? '', $dbip['paragraphs'] ?? []),
                'cta_label'  => is_array($dbip['cta'] ?? null) ? ($dbip['cta']['title'] ?? '') : '',
                'cta_url'    => is_array($dbip['cta'] ?? null) ? ($dbip['cta']['url'] ?? '#') : '#',
            ],
            'blog' => get_field('blog', $id) ?: [],
        ];
    }

    // ACF image sub-field → URL, falling back to the bundled theme asset.
    private function imageUrl($image, string $fallback): string
    {
        if (is_array($image) && ! empty($image['url'])) {
            return $image['url'];
        }

        return Vite::asset($fallback);
    }

    // ---- Fallback: hardcoded content (pre-ACF), branched PL/EN -------------

    private function fromHardcoded(): array
    {
        $isEn = (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';

        return [
            'hero'        => $this->hero($isEn),
            'about'       => $this->about($isEn),
            'memberships' => $this->memberships($isEn),
            'why'         => $this->why($isEn),
            'services'    => $this->services($isEn),
            'dbip'        => $this->dbip($isEn),
        ];
    }

    private function hero(bool $isEn): array
    {
        return [
            'title'  => $isEn ? 'Your accounting' : 'Twoja księgowość',
            'accent' => $isEn ? 'under control.' : 'pod kontrolą.',
            'lead'   => $isEn
                ? 'We take care of your accounting so you can save time and energy. '
                    . 'With us, you can focus on what matters most for your business.'
                : 'Dbamy o Twoją księgowość, abyś mógł oszczędzać czas i energię. '
                    . 'Z nami możesz skupić się na tym, co najważniejsze dla Twojego biznesu.',
            'image_desktop' => Vite::asset('resources/images/hp-hero--desktop.svg'),
            'image_mobile'  => Vite::asset('resources/images/hp-hero--mobile.svg'),
            'image_alt'     => $isEn
                ? 'Document flow diagram: KSeF, InFlow, ARPI Accounting, Tax Office.'
                : 'Schemat obiegu dokumentów: KSeF, InFlow, ARPI Accounting, Urząd Skarbowy.',
        ];
    }

    private function about(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'About us' : 'O nas',
            'lead'    => $isEn
                ? 'We are part of ARPI Group, a Norwegian company where trust and precision '
                    . 'are the flagship values. We have operated in Poland since 2006.'
                : 'Jesteśmy częścią ARPI Group, norweskiej firmy, w której zaufanie '
                    . 'i precyzja stanowią flagowe wartości. Działamy w Polsce od 2006 roku.',
            'stats_desktop' => $isEn
                ? 'We bring together experts specialised in accounting, law and HR & payroll '
                    . 'administration. By choosing ARPI, you entrust your business to professionals who '
                    . 'listen and respond according to the client\'s needs. In 2024 the total turnover of '
                    . 'our clients\' companies exceeded 586,553,123 PLN. Our team manages HR and payroll for '
                    . 'over 3,530 employees each month on behalf of our clients. We offer clients liability '
                    . 'insurance of: 2,200,000 PLN (accounting), 2,200,000 PLN (tax advisory), '
                    . '1,100,000 PLN (HR).'
                : 'Zrzeszamy ekspertów wyspecjalizowanych w zakresie księgowości, prawa '
                    . 'oraz administracji kadrowo-płacowej. Wybierając ARPI, powierzasz swój biznes '
                    . 'opiece profesjonalistów, którzy słuchają i odpowiadają zgodnie z potrzebami klienta. '
                    . 'W 2024 roku całkowity obrót firm naszych klientów przekroczył 586 553 123 PLN. '
                    . 'Nasz zespół zarządza kadrami i płacami ponad 3 530 pracowników miesięcznie dla naszych '
                    . 'klientów. Oferujemy klientom ubezpieczenie OC na kwoty: 2 200 000 PLN (księgowość), '
                    . '2 200 000 PLN (doradztwo podatkowe), 1 100 000 PLN (kadry).',
            'stats_mobile' => $isEn
                ? 'A COMPREHENSIVE APPROACH AND A COORDINATED TEAM ARE THE FOUNDATION'
                : 'KOMPLEKSOWE PODEJŚCIE I ZGRANY ZESPÓŁ TO PODSTAWA',
            'image'     => Vite::asset('resources/images/about-us.png'),
            'image_alt' => $isEn ? 'The ARPI team at work.' : 'Zespół ARPI podczas pracy.',
        ];
    }

    private function memberships(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'We are part of' : 'Jesteśmy częścią',
            'lead'    => $isEn
                ? 'We are part of ARPI Group, a Norwegian company where trust and precision '
                    . 'are the flagship values. We have operated in Poland since 2006.'
                : 'Jesteśmy częścią ARPI Group, norweskiej firmy, w której zaufanie '
                    . 'i precyzja stanowią flagowe wartości. Działamy w Polsce od 2006 roku.',
            'image'     => Vite::asset('resources/images/companies.png'),
            'image_alt' => $isEn
                ? 'Organisations ARPI is a member of: the National Chamber of Tax Advisers, the '
                    . 'Swedish-Polish, Polish-Canadian and Polish-Ukrainian Chambers of Commerce, and the '
                    . 'Scandinavian-Polish Chamber of Commerce.'
                : 'Organizacje, których członkiem jest ARPI: Krajowa Izba Doradców Podatkowych, '
                    . 'Szwedzko-Polska, Polsko-Kanadyjska i Polsko-Ukraińska Izba Gospodarcza, '
                    . 'Scandinavian-Polish Chamber of Commerce.',
        ];
    }

    private function why(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'Why ARPI?' : 'Dlaczego ARPI?',
            'intro'   => $isEn
                ? 'Since 2001 we have supported international companies starting operations on the '
                    . 'Polish market. We have experience selecting the best solutions for growing a '
                    . 'business in Poland.'
                : 'Od 2001 roku wspieramy międzynarodowe firmy rozpoczynające działalność '
                    . 'na polskim rynku. Mamy doświadczenie w dobieraniu najlepszych rozwiązań dla rozwoju '
                    . 'biznesu w Polsce.',
            'hexes'   => $isEn
                ? ['Smooth communication', 'Experience and specialisation', 'International scope',
                    'Comprehensive support', 'Business-supporting applications']
                : ['Sprawna komunikacja', 'Doświadczenie i specjalizacja', 'Międzynarodowy zakres',
                    'Kompleksowe wsparcie', 'Aplikacje wspierające biznes'],
            'statement' => $isEn
                ? 'Since 2001 we have supported international companies starting operations on the '
                    . 'Polish market. We have experience selecting the best solutions for growing a '
                    . 'business in Poland.'
                : 'Od 2001 roku wspieramy międzynarodowe firmy rozpoczynające działalność '
                    . 'na polskim rynku. Mamy doświadczenie w dobieraniu najlepszych rozwiązań dla rozwoju '
                    . 'biznesu w Polsce.',
            'caption' => $isEn
                ? 'Every client receives their own dedicated account manager.'
                : 'Każdy klient otrzymuje swojego indywidualnego opiekuna.',
        ];
    }

    private function services(bool $isEn): array
    {
        $items = [
            ['name' => $isEn ? 'Accounting' : 'Księgowość',              'icon' => 'three-papers',           'slug' => 'ksiegowosc'],
            ['name' => $isEn ? 'HR & Payroll' : 'Kadry i płace',         'icon' => 'people',                 'slug' => 'kadry-i-place'],
            ['name' => $isEn ? 'Tax advisory' : 'Doradztwo podatkowe',   'icon' => 'bulb',                   'slug' => 'doradztwo-podatkowe'],
            ['name' => $isEn ? 'Budgeting & reporting' : 'Budżetowanie i raporty', 'icon' => 'three-bar-graph', 'slug' => 'budzetowanie-i-raporty'],
            ['name' => $isEn ? 'Commercial companies' : 'Spółki handlowe', 'icon' => 'people-and-buildings',  'slug' => 'spolki-handlowe'],
            ['name' => $isEn ? 'Law' : 'Prawo',                          'icon' => 'scales',                 'slug' => 'prawo'],
        ];

        return [
            'heading' => $isEn ? 'Our services' : 'Nasze usługi',
            'items'   => array_map(fn ($item) => [
                'name' => $item['name'],
                'icon' => $item['icon'],
                'url'  => home_url('/uslugi/'.$item['slug']),
            ], $items),
        ];
    }

    private function dbip(bool $isEn): array
    {
        return [
            'heading'    => 'Doing business in Poland',
            'paragraphs' => $isEn
                ? [
                    'We present the latest edition of our knowledge compendium, prepared by ARPI '
                        . 'Accounting experts for entrepreneurs starting their business in Poland.',
                    'Learn the key rights and obligations that apply to entrepreneurs in Poland. Our '
                        . 'publication will help you find your way through the Polish regulatory landscape '
                        . 'and prepare for the challenges ahead.',
                ]
                : [
                    'Prezentujemy najnowszą edycję naszego kompendium wiedzy, opracowanego przez ekspertów '
                        . 'ARPI Accounting z myślą o przedsiębiorcach rozpoczynających swój biznes w Polsce.',
                    'Poznaj najważniejsze prawa i obowiązki dotyczące przedsiębiorców w Polsce. Nasza '
                        . 'publikacja pozwoli Ci odnaleźć się w realiach Polskiego Ładu oraz przygotuje Cię '
                        . 'na nadchodzące wyzwania.',
                ],
            'cta_label'  => $isEn ? 'Explore our publication' : 'Poznaj naszą publikację',
            'cta_url'    => home_url('/dbip-chapters/'),
        ];
    }

    // ---- Blog section: text (ACF-overridable) + auto-derived data ----------

    private function blogMeta(array $acf): array
    {
        $postsPage = (int) get_option('page_for_posts');
        $isEn = (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';

        $defaults = [
            'heading'    => 'Blog',
            'intro'      => $isEn
                ? 'Follow the latest developments in tax and accounting. Every article is prepared '
                    . 'on a regular basis by the team of experts at ARPI.'
                : 'Zapraszamy do śledzenia najnowszych zmian dotyczących podatków i księgowości. '
                    . 'Wszystkie artykuły są regularnie opracowywane przez zespół ekspertów ARPI.',
            'all_label'  => $isEn ? 'All articles' : 'Wszystkie artykuły',
            'more_label' => $isEn ? 'See all' : 'Zobacz wszystkie',
            'empty'      => $isEn
                ? 'The latest articles will appear here soon.'
                : 'Wkrótce pojawią się tu najnowsze artykuły.',
        ];

        // ACF text overrides win when filled in; the URL is always derived.
        $overrides = array_filter([
            'heading'    => $acf['heading'] ?? null,
            'intro'      => $acf['intro'] ?? null,
            'all_label'  => $acf['all_label'] ?? null,
            'more_label' => $acf['more_label'] ?? null,
            'empty'      => $acf['empty'] ?? null,
        ], fn ($v) => ! empty($v));

        return array_merge($defaults, $overrides, [
            'all_url' => $postsPage ? get_permalink($postsPage) : home_url('/blog'),
        ]);
    }

    private function blogCategories(): array
    {
        // Exclude "Uncategorized" in every language (Polylang keeps one per language).
        $excluded = [(int) get_option('default_category')];
        if (function_exists('pll_get_term_translations')) {
            $excluded = array_map('intval', pll_get_term_translations($excluded[0]));
        }

        $categories = get_categories(['hide_empty' => false, 'exclude' => $excluded]);

        // Show only the categories of the current language.
        if (function_exists('pll_current_language') && function_exists('pll_get_term_language')) {
            $lang = pll_current_language();
            $categories = array_values(array_filter(
                $categories,
                fn ($cat) => pll_get_term_language($cat->term_id) === $lang
            ));
        }

        return $categories;
    }

    private function latestPosts(): array
    {
        return get_posts(['post_type' => 'post', 'numberposts' => 3, 'post_status' => 'publish']);
    }
}
