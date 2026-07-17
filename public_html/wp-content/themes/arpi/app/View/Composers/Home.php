<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use Roots\Acorn\Assets\Contracts\Vite;

class Home extends Composer
{
    protected static $views = ['front-page'];

    public function with(): array
    {
        return [
            'hero'           => $this->hero(),
            'about'          => $this->about(),
            'memberships'    => $this->memberships(),
            'why'            => $this->why(),
            'services'       => $this->services(),
            'dbip'           => $this->dbip(),
            'blog'           => $this->blogMeta(),
            'blogCategories' => $this->blogCategories(),
            'latestPosts'    => $this->latestPosts(),
        ];
    }

    // TODO: swap the static getters below to get_field(..., 'option') in the ACF phase.

    private function hero(): array
    {
        return [
            'title'     => 'Twoja księgowość',
            'accent'    => 'pod kontrolą.',
            'lead'      => 'Dbamy o Twoją księgowość, abyś mógł oszczędzać czas i energię. '
                . 'Z nami możesz skupić się na tym, co najważniejsze dla Twojego biznesu.',
            'image_desktop'     => 'resources/images/hp-hero--desktop.svg',
            'image_mobile'     => 'resources/images/hp-hero--mobile.svg',
            'image_alt' => 'Schemat obiegu dokumentów: KSeF, InFlow, ARPI Accounting, Urząd Skarbowy.',
        ];
    }

    private function about(): array
    {
        return [
            'heading'   => 'O nas',
            'lead'      => 'Jesteśmy częścią ARPI Group, norweskiej firmy, w której zaufanie '
                . 'i precyzja stanowią flagowe wartości. Działamy w Polsce od 2006 roku.',
            'stats_desktop'     => 'Zrzeszamy ekspertów wyspecjalizowanych w zakresie księgowości, prawa '
                . 'oraz administracji kadrowo-płacowej. Wybierając ARPI, powierzasz swój biznes '
                . 'opiece profesjonalistów, którzy słuchają i odpowiadają zgodnie z potrzebami klienta. '
                . 'W 2024 roku całkowity obrót firm naszych klientów przekroczył 586 553 123 PLN. '
                . 'Nasz zespół zarządza kadrami i płacami ponad 3 530 pracowników miesięcznie dla naszych '
                . 'klientów. Oferujemy klientom ubezpieczenie OC na kwoty: 2 200 000 PLN (księgowość), '
                . '2 200 000 PLN (doradztwo podatkowe), 1 100 000 PLN (kadry).',
            'stats_mobile' => 'KOMPLEKSOWE PODEJŚCIE I ZGRANY ZESPÓŁ TO PODSTAWA',
            'image'     => 'resources/images/about-us.png',
            'image_alt' => 'Zespół ARPI podczas pracy.',
        ];
    }

    private function memberships(): array
    {
        return [
            'heading'   => 'Jesteśmy częścią',
            'lead'      => 'Jesteśmy częścią ARPI Group, norweskiej firmy, w której zaufanie '
                . 'i precyzja stanowią flagowe wartości. Działamy w Polsce od 2006 roku.',
            'image'     => 'resources/images/companies.png',
            'image_alt' => 'Organizacje, których członkiem jest ARPI: Krajowa Izba Doradców Podatkowych, '
                . 'Szwedzko-Polska, Polsko-Kanadyjska i Polsko-Ukraińska Izba Gospodarcza, '
                . 'Scandinavian-Polish Chamber of Commerce.',
        ];
    }

    private function why(): array
    {
        return [
            'heading'   => 'Dlaczego ARPI?',
            'intro'     => 'Od 2001 roku wspieramy międzynarodowe firmy rozpoczynające działalność '
                . 'na polskim rynku. Mamy doświadczenie w dobieraniu najlepszych rozwiązań dla rozwoju '
                . 'biznesu w Polsce.',
            'hexes'     => [
                'Sprawna komunikacja',
                'Doświadczenie i specjalizacja',
                'Międzynarodowy zakres',
                'Kompleksowe wsparcie',
                'Aplikacje wspierające biznes',
            ],
            'statement' => 'Od 2001 roku wspieramy międzynarodowe firmy rozpoczynające działalność '
                . 'na polskim rynku. Mamy doświadczenie w dobieraniu najlepszych rozwiązań dla rozwoju '
                . 'biznesu w Polsce.',
            'caption'   => 'Każdy klient otrzymuje swojego indywidualnego opiekuna.',
        ];
    }

    private function services(): array
    {
        return [
            ['name' => 'Księgowość',           'icon' => 'three-papers',          'url' => home_url('/uslugi/ksiegowosc')],
            ['name' => 'Kadry i płace',        'icon' => 'people',              'url' => home_url('/uslugi/kadry-i-place')],
            ['name' => 'Doradztwo podatkowe',  'icon' => 'bulb',                'url' => home_url('/uslugi/doradztwo-podatkowe')],
            ['name' => 'Budżetowanie i raporty','icon' => 'three-bar-graph',    'url' => home_url('/uslugi/budzetowanie-i-raporty')],
            ['name' => 'Spółki handlowe',      'icon' => 'people-and-buildings','url' => home_url('/uslugi/spolki-handlowe')],
            ['name' => 'Prawo',                'icon' => 'scales',              'url' => home_url('/uslugi/prawo')],
        ];
    }

    private function dbip(): array
    {
        return [
            'heading'    => 'Doing business in Poland',
            'paragraphs' => [
                'Prezentujemy najnowszą edycję naszego kompendium wiedzy, opracowanego przez ekspertów '
                    . 'ARPI Accounting z myślą o przedsiębiorcach rozpoczynających swój biznes w Polsce.',
                'Poznaj najważniejsze prawa i obowiązki dotyczące przedsiębiorców w Polsce. Nasza '
                    . 'publikacja pozwoli Ci odnaleźć się w realiach Polskiego Ładu oraz przygotuje Cię '
                    . 'na nadchodzące wyzwania.',
            ],
            'cta_label'  => 'Poznaj naszą publikację',
            'cta_url'    => home_url('/dbip-chapters/'),
        ];
    }

    private function blogMeta(): array
    {
        $postsPage = (int) get_option('page_for_posts');
        $isEn = (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';

        return [
            'heading'   => 'Blog',
            'intro'     => $isEn
                ? 'Follow the latest developments in tax and accounting. Every article is prepared '
                    . 'on a regular basis by the team of experts at ARPI.'
                : 'Zapraszamy do śledzenia najnowszych zmian dotyczących podatków i księgowości. '
                    . 'Wszystkie artykuły są regularnie opracowywane przez zespół ekspertów ARPI.',
            'all_label' => $isEn ? 'All articles' : 'Wszystkie artykuły',
            'more_label' => $isEn ? 'See all' : 'Zobacz wszystkie',
            'empty'     => $isEn
                ? 'The latest articles will appear here soon.'
                : 'Wkrótce pojawią się tu najnowsze artykuły.',
            'all_url'   => $postsPage ? get_permalink($postsPage) : home_url('/blog'),
        ];
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
