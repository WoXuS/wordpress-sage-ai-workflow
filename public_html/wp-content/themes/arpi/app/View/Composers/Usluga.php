<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Usluga extends Composer
{
    protected static $views = ['single-usluga'];

    public function with(): array
    {
        $content = $this->fromAcf() ?? $this->fromHardcoded();

        return [
            'hero' => $content['hero'],
            'scope_intro' => $content['scope_intro'] ?? null,
            'scope' => $content['scope'],
            'body' => $content['body'],
            'reports' => $content['reports'],
            'others' => $this->others(),
            'labels' => $this->labels(),
        ];
    }

    // ---- Primary source: ACF (group_usluga_tresc) --------------------------

    private function fromAcf(): ?array
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $hero = get_field('hero');

        if (! $hero || empty($hero['lead'])) {
            return null; // not filled in yet — fall back to hardcoded
        }

        $reports = get_field('reports') ?: [];

        return [
            'hero' => [
                'icon' => $this->icon($hero),
                'title' => $this->title(get_the_title()),
                'lead' => $hero['lead'] ?? '',
            ],
            'scope_intro' => get_field('scope_intro') ?: null,
            'scope' => array_map(fn ($row) => [
                'icon' => $this->icon($row),
                'label' => $row['label'] ?? '',
            ], get_field('scope') ?: []),
            'body' => get_field('body') ?: '',
            'reports' => [
                'heading' => $reports['heading'] ?? '',
                'items' => array_map(fn ($row) => $row['text'] ?? '', $reports['items'] ?? []),
            ],
        ];
    }

    // Resolve the icon-picker sub-fields (source / name / file) into a shape
    // the <x-dynamic-icon> component renders.
    private function icon(array $data): array
    {
        if (($data['icon_source'] ?? 'library') === 'custom' && ! empty($data['icon_file'])) {
            $file = $data['icon_file'];

            return [
                'type' => 'file',
                'url' => is_array($file) ? ($file['url'] ?? '') : wp_get_attachment_image_url((int) $file, 'full'),
            ];
        }

        return ['type' => 'svg', 'name' => $data['icon_name'] ?? 'three-papers'];
    }

    // ---- Fallback: hardcoded PL content (pre-ACF MVP) ----------------------

    private function fromHardcoded(): array
    {
        $raw = $this->content();

        return [
            'hero' => [
                'icon' => ['type' => 'svg', 'name' => $raw['hero']['icon']],
                'title' => $this->title($raw['hero']['title'] ?: get_the_title()),
                'lead' => $raw['hero']['lead'],
            ],
            'scope' => array_map(fn ($item) => [
                'icon' => ['type' => 'svg', 'name' => $item['icon']],
                'label' => $item['label'],
            ], $raw['scope']),
            'body' => implode('', array_map(fn ($p) => '<p>'.$p.'</p>', $raw['body'])),
            'reports' => $raw['reports'],
        ];
    }

    private function content(): array
    {
        $pages = [
            'ksiegowosc' => [
                'hero' => [
                    'icon' => 'three-papers',
                    'title' => 'Księgowość',
                    'lead' => 'Z ARPI Accounting możesz swobodnie decydować o kształcie współpracy, ponieważ specjalizujemy się w szerokim spektrum usług administracyjno-księgowych. Wystarczy, że ustalisz zakresy tematyczne i wskażesz nam czynności, które są dla Ciebie najbardziej istotne.',
                ],
                'scope' => [
                    ['icon' => 'four-bar-graph', 'label' => 'ewidencje zdarzeń gospodarczych'],
                    ['icon' => 'list-on-paper', 'label' => 'roczne sprawozdania finansowe'],
                    ['icon' => 'list-with-check', 'label' => 'zestawienia obrotów i sald'],
                    ['icon' => 'file', 'label' => 'deklaracje podatkowe [CIT, VAT]'],
                    ['icon' => 'shield', 'label' => 'ewidencje środków trwałych klienta'],
                    ['icon' => 'handshake-percent', 'label' => 'wsparcie przy kontrolach US i współpraca z audytorami'],
                    ['icon' => 'people-and-buildings', 'label' => 'sprawozdania do głównego urzędu statystycznego i NBP'],
                ],
                'body' => [
                    'Jesteśmy na bieżąco z najnowszymi zmianami w regulacjach prawnych i technicznych aspektach księgowości, dzięki czemu zapewniamy obsługę rachunkową na najwyższym poziomie. Kontrola nad poprawnością i aktualnością naszych procedur to codzienny priorytet naszych specjalistów.',
                    'Opracowaliśmy własne unikalne narzędzie do obsługi faktur – InFlow – które pozwala nam oferować całkowitą lub częściową obsługę operacji bankowych naszych klientów. Bezpieczny system zapewnia kompletną kontrolę merytoryczną przesłanych i wychodzących dokumentów. Po określeniu częstotliwości i charakteru zleceń, zajmiemy się realizacją oraz przygotujemy dla Ciebie codzienny raport z listą podjętych czynności.',
                    'Specjalizujemy się również w podstawowych i zaawansowanych raportach finansowych.',
                ],
                'reports' => [
                    'heading' => 'Poza najważniejszymi raportami, jak Bilans czy Rachunek Zysków i Strat, oferujemy również przygotowanie:',
                    'items' => [
                        'raportu sald intercompany (na kontach z jednostkami powiązanymi)',
                        'raportów oferujących podział kosztów pod względem zmiennym: projekt / rodzaje kosztów / '
                        .'jednostki ponoszące koszty',
                        'analizy wyników, porównanie przychodów do kosztów (EBIT w rozbiciu na projekty)',
                        'zestawienie wyników miesięcznych',
                        'wiekowanie należności',
                    ],
                ],
            ],
        ];

        $slug = get_post_field('post_name', get_the_ID());

        return $pages[$slug] ?? $this->fallback();
    }

    private function fallback(): array
    {
        return [
            'hero' => ['icon' => 'three-papers', 'title' => get_the_title(), 'lead' => ''],
            'scope' => [],
            'body' => [],
            'reports' => ['heading' => '', 'items' => []],
        ];
    }

    // "Pozostałe usługi" grid: every other usluga in the current language,
    // ordered by menu_order. Content comes from each sibling's ACF, so it is
    // editable in wp-admin and follows the Polylang translation of that post.
    private function others(): array
    {
        $posts = get_posts([
            'post_type' => 'usluga',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'exclude' => [get_the_ID()],
            'lang' => function_exists('pll_current_language') ? pll_current_language() : '',
        ]);

        return array_map(fn ($post) => [
            'name' => $this->title(get_the_title($post)),
            'url' => get_permalink($post),
            'icon' => $this->icon(get_field('hero', $post->ID) ?: []),
            'desc' => (string) get_field('tile_excerpt', $post->ID),
        ], $posts);
    }

    // WP titles come with HTML entities already encoded (e.g. "Payroll &amp; HR");
    // decode so Blade's {{ }} escapes exactly once instead of double-encoding.
    private function title(string $title): string
    {
        return html_entity_decode($title, ENT_QUOTES);
    }

    private function labels(): array
    {
        $isEn = (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';

        return [
            'others_heading' => $isEn ? 'Other services' : 'Pozostałe usługi',
            'more' => $isEn ? 'Learn more' : 'Więcej',
        ];
    }
}
