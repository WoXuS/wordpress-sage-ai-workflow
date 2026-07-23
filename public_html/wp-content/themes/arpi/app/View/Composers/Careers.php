<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use Illuminate\Support\Facades\Vite;

class Careers extends Composer
{
    protected static $views = ['template-careers'];

    protected function with(): array
    {
        $isEn = $this->isEn();

        return [
            'hero'      => $this->hero($isEn),
            'intro'     => $this->intro($isEn),
            'mosaic'    => $this->mosaic($isEn),
            'benefits'  => $this->benefits($isEn),
            'positions' => $this->positions($isEn),
            'cta'       => $this->cta($isEn),
        ];
    }

    private function isEn(): bool
    {
        return (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';
    }

    private function hero(bool $isEn): array
    {
        return [
            'title'  => $isEn ? 'Grow with' : 'Rozwijaj się',
            'accent' => $isEn ? 'ARPI' : 'z ARPI',
            'lead'   => $isEn
                ? 'We are a team of accounting, tax and HR specialists who value trust, precision '
                    . 'and a friendly atmosphere. Join us and build your career on solid foundations.'
                : 'Jesteśmy zespołem specjalistów księgowości, podatków i kadr, dla których liczą się '
                    . 'zaufanie, precyzja i dobra atmosfera. Dołącz do nas i buduj karierę na solidnych '
                    . 'fundamentach.',
        ];
    }

    private function intro(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'Why work with us?' : 'Dlaczego warto?',
            'lead'    => $isEn
                ? 'People are the heart of ARPI. We invest in their growth every day.'
                : 'Ludzie są sercem ARPI. Każdego dnia inwestujemy w ich rozwój.',
            'paragraphs' => $isEn
                ? [
                    'Since 2006 we have supported companies entering the Polish market, and we have '
                        . 'grown together with the people who make ARPI what it is. We believe that a stable, '
                        . 'well-supported team is the foundation of the quality our clients trust us for.',
                    'With us you gain real responsibility from day one, a mentor to guide your first steps, '
                        . 'and the space to specialise in the area that suits you best — accounting, HR & payroll, '
                        . 'tax advisory or law. We work as one team, share knowledge openly and celebrate wins together.',
                ]
                : [
                    'Od 2006 roku wspieramy firmy wchodzące na polski rynek i rośniemy razem z ludźmi, '
                        . 'którzy tworzą ARPI. Wierzymy, że stabilny i dobrze wspierany zespół to fundament '
                        . 'jakości, za którą cenią nas klienci.',
                    'U nas od pierwszego dnia zyskujesz realną odpowiedzialność, opiekuna, który poprowadzi '
                        . 'Twoje pierwsze kroki, oraz przestrzeń do specjalizacji w obszarze, który pasuje Ci '
                        . 'najbardziej — księgowość, kadry i płace, doradztwo podatkowe czy prawo. Pracujemy jak '
                        . 'jeden zespół, otwarcie dzielimy się wiedzą i wspólnie świętujemy sukcesy.',
                ],
        ];
    }

    /**
     * Photo/stat mosaic — a "life at ARPI" collage. Tiles carry their own grid
     * span so the Blade layout stays declarative; `dense` auto-flow tessellates
     * the leftover gaps. Photos reuse bundled theme assets until real HR shots land.
     */
    private function mosaic(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'Life at ARPI' : 'Życie w ARPI',
            'tiles'   => [
                [
                    'kind'  => 'photo',
                    'image' => Vite::asset('resources/images/about-us.png'),
                    'alt'   => $isEn ? 'The ARPI team at work.' : 'Zespół ARPI podczas pracy.',
                    'span'  => 'col-span-2 row-span-2',
                ],
                [
                    'kind'  => 'stat',
                    'value' => '2006',
                    'label' => $isEn ? 'on the market since' : 'na rynku od',
                    'span'  => 'aspect-square',
                ],
                [
                    'kind'  => 'stat',
                    'value' => '18+',
                    'label' => $isEn ? 'years of experience' : 'lat doświadczenia',
                    'span'  => 'aspect-square',
                ],
                [
                    'kind'  => 'accent',
                    'image' => Vite::asset('resources/images/purple-hexagon.svg'),
                    'span'  => 'aspect-square',
                ],
                [
                    'kind'  => 'stat',
                    'value' => '2',
                    'label' => $isEn ? 'offices in Poland' : 'biura w Polsce',
                    'span'  => 'aspect-square',
                ],
                [
                    'kind'  => 'quote',
                    'text'  => $isEn
                        ? 'The best part of ARPI is the people you can always count on.'
                        : 'Najlepsze w ARPI są ludzie, na których zawsze można polegać.',
                    'attribution' => $isEn ? 'Anna, HR & Payroll' : 'Anna, kadry i płace',
                    'span'        => 'col-span-2 md:col-span-4',
                ],
            ],
        ];
    }

    private function benefits(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'What we offer' : 'Co oferujemy',
            'lead'    => $isEn
                ? 'Concrete support at every stage of your career.'
                : 'Konkretne wsparcie na każdym etapie Twojej kariery.',
            'items'   => $isEn
                ? [
                    ['icon' => 'graph-in-hand',      'title' => 'Real growth',        'text' => 'Training, certifications and a clear path to specialisation in your field.'],
                    ['icon' => 'hands-over-person',  'title' => 'Stable employment',  'text' => 'A long-term relationship, an employment contract and a team you can rely on.'],
                    ['icon' => 'people',             'title' => 'Friendly team',      'text' => 'Open communication, shared knowledge and colleagues who have your back.'],
                    ['icon' => 'calendar',           'title' => 'Work–life balance',  'text' => 'Flexible hours and a hybrid model adjusted to your responsibilities.'],
                    ['icon' => 'shield',             'title' => 'Security',           'text' => 'Private healthcare, benefits and the backing of the international ARPI Group.'],
                    ['icon' => 'handshake',          'title' => 'Real impact',        'text' => 'Responsibility from day one and a genuine influence on how we work.'],
                ]
                : [
                    ['icon' => 'graph-in-hand',      'title' => 'Realny rozwój',      'text' => 'Szkolenia, certyfikaty i jasna ścieżka specjalizacji w Twoim obszarze.'],
                    ['icon' => 'hands-over-person',  'title' => 'Stabilne zatrudnienie', 'text' => 'Długofalowa współpraca, umowa o pracę i zespół, na którym można polegać.'],
                    ['icon' => 'people',             'title' => 'Zgrany zespół',      'text' => 'Otwarta komunikacja, dzielenie się wiedzą i wsparcie współpracowników.'],
                    ['icon' => 'calendar',           'title' => 'Równowaga',          'text' => 'Elastyczne godziny i model hybrydowy dopasowany do Twoich obowiązków.'],
                    ['icon' => 'shield',             'title' => 'Bezpieczeństwo',     'text' => 'Prywatna opieka medyczna, benefity i zaplecze międzynarodowej ARPI Group.'],
                    ['icon' => 'handshake',          'title' => 'Realny wpływ',       'text' => 'Odpowiedzialność od pierwszego dnia i rzeczywisty wpływ na to, jak pracujemy.'],
                ],
        ];
    }

    /**
     * Open roles. `email` drives a prefilled mailto; the composer keeps a single
     * recruitment address so it is trivial to swap for ACF later.
     */
    private function positions(bool $isEn): array
    {
        $email = 'rekrutacja@arpiaccounting.com';

        $roles = $isEn
            ? [
                ['title' => 'Independent Accountant',      'location' => 'Warszawa', 'type' => 'Full-time'],
                ['title' => 'HR & Payroll Specialist',     'location' => 'Rzeszów',  'type' => 'Full-time'],
                ['title' => 'Junior Accountant',           'location' => 'Warszawa', 'type' => 'Full-time · Hybrid'],
                ['title' => 'Tax Advisory Assistant',      'location' => 'Warszawa', 'type' => 'Full-time'],
            ]
            : [
                ['title' => 'Samodzielna Księgowa / Księgowy', 'location' => 'Warszawa', 'type' => 'Pełny etat'],
                ['title' => 'Specjalista ds. kadr i płac',     'location' => 'Rzeszów',  'type' => 'Pełny etat'],
                ['title' => 'Młodszy Księgowy / Księgowa',     'location' => 'Warszawa', 'type' => 'Pełny etat · Hybrydowo'],
                ['title' => 'Asystent doradcy podatkowego',    'location' => 'Warszawa', 'type' => 'Pełny etat'],
            ];

        $subject = $isEn ? 'Job application' : 'Aplikacja rekrutacyjna';

        return [
            'heading' => $isEn ? 'Open positions' : 'Otwarte rekrutacje',
            'lead'    => $isEn
                ? 'Found a role that fits? Send us your CV — we read every application.'
                : 'Znalazłeś ofertę dla siebie? Wyślij nam CV — czytamy każde zgłoszenie.',
            'apply_label' => $isEn ? 'Apply' : 'Aplikuj',
            'items'   => array_map(fn ($role) => [
                'title'    => $role['title'],
                'location' => $role['location'],
                'type'     => $role['type'],
                'apply_url' => 'mailto:' . $email . '?subject=' . rawurlencode($subject . ' — ' . $role['title']),
            ], $roles),
            'open' => [
                'heading' => $isEn ? "Don't see the right role?" : 'Nie ma oferty dla Ciebie?',
                'text'    => $isEn
                    ? 'Send us an open application — we are always glad to meet talented people.'
                    : 'Wyślij aplikację otwartą — zawsze chętnie poznamy utalentowane osoby.',
                'label'    => $isEn ? 'Send an open application' : 'Wyślij aplikację otwartą',
                'apply_url' => 'mailto:' . $email . '?subject=' . rawurlencode($isEn ? 'Open application' : 'Aplikacja otwarta'),
            ],
        ];
    }

    private function cta(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'Ready to join us?' : 'Gotowy, by do nas dołączyć?',
            'lead'    => $isEn
                ? 'Write to us and take the first step towards a career at ARPI.'
                : 'Napisz do nas i zrób pierwszy krok w stronę kariery w ARPI.',
            'label'    => $isEn ? 'Send your CV' : 'Wyślij CV',
            'apply_url' => 'mailto:rekrutacja@arpiaccounting.com',
        ];
    }
}
