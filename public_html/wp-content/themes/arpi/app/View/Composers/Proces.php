<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Vite;
use Roots\Acorn\View\Composer;

class Proces extends Composer
{
    protected static $views = ['template-proces'];

    public function with(): array
    {
        $content = $this->fromAcf() ?? ($this->isEn() ? $this->en() : $this->pl());

        return [
            'hero'   => $content['hero'],
            'stages' => $content['stages'],
        ];
    }

    private function isEn(): bool
    {
        return (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';
    }

    // Located by page template, so PL/EN pages each edit their own content; null until hero filled, then pl()/en() fallback renders.
    private function fromAcf(): ?array
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $hero = get_field('hero');

        if (! $hero || empty($hero['title'])) {
            return null;
        }

        return [
            'hero' => [
                'title'    => $hero['title'] ?? '',
                'intro'    => $hero['intro'] ?? '',
                'subtitle' => $hero['subtitle'] ?? '',
            ],
            'stages' => array_map(fn ($stage) => $this->stage($stage), get_field('stages') ?: []),
        ];
    }

    // `logo` is emitted only when its text is filled; `list` collapses {lead, desc} into the [$lead, $desc] pairs the view destructures.
    private function stage(array $row): array
    {
        $stage = [
            'tone'      => $row['tone'] ?? 'outline',
            'icon'      => $row['icon_name'] ?? 'grouped-papers',
            'hex_label' => $row['hex_label'] ?? '',
            'heading'   => $row['heading'] ?? '',
            'tags'      => array_map(fn ($r) => $r['text'] ?? '', $row['tags'] ?? []),
            'paras'     => array_map(fn ($r) => $r['text'] ?? '', $row['paras'] ?? []),
        ];

        $logo = $row['logo'] ?? [];
        if (! empty($logo['text'])) {
            $stage['logo'] = ['symbol' => $logo['icon_name'] ?? '', 'text' => $logo['text']];
        }

        $list = array_map(fn ($r) => [$r['lead'] ?? '', $r['desc'] ?? ''], $row['list'] ?? []);
        if ($list) {
            $stage['list_intro'] = $row['list_intro'] ?? '';
            $stage['list'] = $list;
        }

        return $stage;
    }

    private function pl(): array
    {
        return [
            'hero' => [
                'title'    => 'Proces ARPI',
                'intro'    => 'Poznaj naszą ofertę opieki nad procesem księgowym i odkryj, jak współpraca '
                    . 'z ARPI Accounting może przynieść wymierne korzyści finansowe.',
                'subtitle' => 'Jako zaufany partner, kierujemy Twoje rozliczenia finansowe w dobrą stronę '
                    . '— profesjonalnie, nowocześnie i z uwzględnieniem indywidualnych potrzeb.',
            ],
            'stages' => [
                [
                    'tone'      => 'outline',
                    'icon'      => 'grouped-papers',
                    'hex_label' => 'KSeF',
                    'heading'   => 'Etap I. Przygotowanie odpowiedniej dokumentacji przez Klienta',
                    'tags'      => ['Pracownik przygotowuje niezbędne dokumenty'],
                    'paras'     => [
                        'Nasz pomysł na księgowość jest prosty. Zleć nam całą lub finalną część księgowości '
                        . 'i zyskaj spokój oraz dostęp do specjalistycznej wiedzy. ARPI Accounting zadba '
                        . 'o najistotniejszy element procesu: o ostateczne rozliczenie Twoich finansów '
                        . 'i bezpośredni kontakt z Urzędami. Obowiązki przekazu plików po stronie Klienta '
                        . 'pozwalają na szybkie i skuteczne rozpoczęcie pracy przez ARPI Accounting.',
                    ],
                ],
                [
                    'tone'      => 'outline',
                    'icon'      => 'inflow',
                    'hex_label' => 'InFlow',
                    'heading'   => 'Etap II. Klienci ARPI Accounting otrzymują dostęp do programu InFlow',
                    'tags'      => ['Pracownik wprowadza dane do systemu InFlow'],
                    'paras'     => [
                        'InFlow, nasza autorska aplikacja, pozwala pracować z dokumentami księgowymi online '
                        . 'z poziomu zarówno strony web, jak i aplikacji mobilnej. Aplikację mobilną można '
                        . 'w wygodny sposób pobrać ze sklepów Apple Store i Google Play. Wszyscy Klienci '
                        . 'ARPI Accounting otrzymują bezpłatny dostęp do InFlow — dzięki czemu nie muszą '
                        . 'opłacać i posiadać alternatywnych programów wspomagających księgowanie i wymianę '
                        . 'dokumentów. W ramach naszej usługi, pracownik Klienta samodzielnie wprowadza '
                        . 'dokumenty księgowe do systemu.',
                    ],
                ],
                [
                    'tone'       => 'solid',
                    'icon'       => 'handshake',
                    'hex_label'  => 'ARPI',
                    'logo'      => ['image' => Vite::asset('resources/images/logo.png'), 'alt' => 'ARPI Accounting'],
                    'heading'    => 'Etap III. ARPI dokonuje ostatecznego księgowania',
                    'tags'       => [
                        'ARPI weryfikuje aspekty podatkowe',
                        'ARPI dokonuje ostatecznego księgowania',
                        'ARPI wysyła poprawione dokumenty do urzędu skarbowego',
                    ],
                    'list_intro' => 'Zlecenie finalnej części księgowości firmie zewnętrznej niesie ze sobą '
                        . 'wiele korzyści:',
                    'list'       => [
                        ['Oszczędność czasu i zasobów', 'Przekazując finalne rozliczenia profesjonalistom, '
                            . 'firma może skupić się na swojej podstawowej działalności, zamiast angażować '
                            . 'czas i zasoby w złożone procesy księgowe.'],
                        ['Gwarancja ciągłości operacyjnej', 'Profesjonalna firma księgowa zapewnia, '
                            . 'że wszelkie zadania są wykonywane terminowo i zgodnie z przepisami, '
                            . 'co minimalizuje ryzyko przerw w funkcjonowaniu finansowym firmy.'],
                        ['Dostęp do eksperckiej wiedzy', 'Zewnętrzni specjaliści dysponują szeroką wiedzą '
                            . 'na temat aktualnych przepisów i zmian prawnych, co pozwala na uniknięcie '
                            . 'błędów i korzystanie z najnowszych rozwiązań księgowych.'],
                        ['Redukcja ryzyka błędów', 'Dzięki profesjonalnej weryfikacji dokumentów i rozliczeń, '
                            . 'ryzyko błędów księgowych jest znacząco zmniejszone, co chroni firmę '
                            . 'przed potencjalnymi problemami z urzędami skarbowymi.'],
                        ['Większa elastyczność i skalowalność', 'Zlecenie części księgowości na zewnątrz '
                            . 'umożliwia lepsze dostosowanie usług do bieżących potrzeb firmy, co jest '
                            . 'szczególnie korzystne w przypadku sezonowych wahań w obciążeniu pracą.'],
                        ['Brak potrzeby inwestycji w systemy księgowe', 'Dzięki współpracy z firmą zewnętrzną '
                            . 'nie ma konieczności zakupu, wdrażania ani aktualizacji drogich systemów '
                            . 'księgowych — wszystkie niezbędne narzędzia zapewnia zewnętrzny partner.'],
                        ['Spokój i pewność', 'Współpraca z firmą specjalizującą się w księgowości daje '
                            . 'przedsiębiorcy poczucie bezpieczeństwa, że sprawy finansowe są w dobrych '
                            . 'rękach i realizowane zgodnie z obowiązującymi normami.'],
                    ],
                ],
            ],
        ];
    }

    private function en(): array
    {
        return [
            'hero' => [
                'title'    => 'ARPI process',
                'intro'    => 'Explore our accounting care service offering and discover how partnering '
                    . 'with ARPI Accounting can deliver tangible financial benefits for your business.',
                'subtitle' => "As your trusted advisor, we'll steer your financial processes in the right "
                    . 'direction — professionally, with modern solutions, and tailored to your individual needs.',
            ],
            'stages' => [
                [
                    'tone'      => 'outline',
                    'icon'      => 'grouped-papers',
                    'hex_label' => 'KSeF',
                    'heading'   => 'Stage I. Preparation of relevant documentation by the client',
                    'tags'      => ['Employee prepares all required documentation'],
                    'paras'     => [
                        'Our idea of accounting is simple. Outsource all or the final part of your accounting '
                        . 'to us and gain peace of mind and access to expertise. ARPI Accounting will take care '
                        . 'of the most essential part of the process: the final settlement of your finances '
                        . "and direct contact with the Authorities. File transfer duties on the client's side "
                        . 'allow ARPI Accounting to get started quickly and efficiently.',
                    ],
                ],
                [
                    'tone'      => 'outline',
                    'icon'      => 'inflow',
                    'hex_label' => 'InFlow',
                    'heading'   => 'Stage II. ARPI Accounting customers receive access to the InFlow program',
                    'tags'      => ['Employee enters the data into the InFlow system'],
                    'paras'     => [
                        'InFlow, our homemade app, allows you to work with accounting documents online from both '
                        . 'the website and the mobile application. The mobile application can be conveniently '
                        . 'downloaded from the Apple Store and Google Play. All ARPI Accounting Clients receive '
                        . 'free access to InFlow — so they do not have to pay for and have alternative programs '
                        . "supporting accounting and document exchange. As part of our service, the Client's "
                        . 'employee independently enters accounting documents into the system.',
                    ],
                ],
                [
                    'tone'       => 'solid',
                    'icon'       => 'handshake',
                    'hex_label'  => 'ARPI',
                    'logo'      => ['image' => Vite::asset('resources/images/logo.png'), 'alt' => 'ARPI Accounting'],
                    'heading'    => 'Stage III. ARPI makes the final accounting',
                    'tags'       => [
                        'ARPI verifies the tax-related aspects',
                        'ARPI completes the final accounting process',
                        'ARPI submits the corrected documents to the Tax Office',
                    ],
                    'list_intro' => 'Outsourcing the final part of your accounting to an external company '
                        . 'brings many benefits:',
                    'list'       => [
                        ['Saving time and resources', 'By outsourcing final accounting to professionals, '
                            . 'a company can focus on its core business instead of investing time and resources '
                            . 'in complex accounting processes.'],
                        ['Guaranteed operational continuity', 'A professional accounting firm ensures that all '
                            . 'tasks are completed on time and in accordance with regulations, which minimizes '
                            . 'the risk of interruptions in the financial functioning of the company.'],
                        ['Access to expert knowledge', 'External specialists have extensive knowledge of current '
                            . 'regulations and legal changes, which allows you to avoid mistakes and use the '
                            . 'latest accounting solutions.'],
                        ['Reducing the risk of errors', 'Thanks to professional verification of documents and '
                            . 'settlements, the risk of accounting errors is significantly reduced, which '
                            . 'protects the company from potential problems with tax offices.'],
                        ['Greater flexibility and scalability', 'Outsourcing part of your accounting allows you '
                            . "to better tailor your services to your company's current needs, which is "
                            . 'especially beneficial in the case of seasonal fluctuations in workload.'],
                        ['No need to invest in accounting systems', 'By cooperating with an external company, '
                            . 'there is no need to purchase, implement or update expensive accounting systems '
                            . '— all the necessary tools are provided by the external partner.'],
                        ['Peace and confidence', 'Collaboration with a company specializing in accounting gives '
                            . 'entrepreneurs a sense of security that financial matters are in good hands and '
                            . 'are carried out in accordance with applicable standards.'],
                    ],
                ],
            ],
        ];
    }
}
