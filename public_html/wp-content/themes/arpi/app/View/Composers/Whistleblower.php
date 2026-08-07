<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Whistleblower extends Composer
{
    protected static $views = ['template-sygnalista'];

    protected function with(): array
    {
        $isEn = $this->isEn();

        return [
            'hero' => $this->hero($isEn),
            'info' => $this->info($isEn),
            'form' => $this->form($isEn),
        ];
    }

    private function isEn(): bool
    {
        return (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';
    }

    private function hero(bool $isEn): array
    {
        return [
            'title'  => $isEn ? 'Whistleblower' : 'Sygnalista',
            'accent' => $isEn ? 'channel' : '',
            'lead'   => $isEn
                ? 'A safe, confidential channel for reporting violations. You can report anonymously — '
                    . 'no identifying detail is required.'
                : 'Bezpieczny, poufny kanał do zgłaszania naruszeń. Zgłoszenia możesz przekazać anonimowo '
                    . '— nie musisz podawać żadnych danych identyfikujących.',
        ];
    }

    private function info(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'Before you report' : 'Zanim zgłosisz',
            'paragraphs' => $isEn
                ? [
                    'This channel lets you report violations of the law or internal rules you have noticed '
                        . 'in connection with ARPI Accounting. Reports are handled with full confidentiality by '
                        . 'the designated persons only.',
                    'You may report anonymously. If you leave a contact e-mail, we will confirm receipt and '
                        . 'may follow up for clarification — your identity stays protected either way.',
                    'You can attach documents that support your report (PDF, DOC, DOCX, JPG, PNG). Attachments '
                        . 'are stored securely and are not publicly accessible.',
                ]
                : [
                    'Ten kanał służy do zgłaszania naruszeń prawa lub wewnętrznych zasad, które zauważyłeś '
                        . 'w związku z ARPI Accounting. Zgłoszenia są obsługiwane z zachowaniem pełnej poufności '
                        . 'wyłącznie przez wyznaczone osoby.',
                    'Zgłoszenie możesz przekazać anonimowo. Jeśli podasz adres e-mail do kontaktu, potwierdzimy '
                        . 'przyjęcie zgłoszenia i będziemy mogli dopytać o szczegóły — Twoja tożsamość i tak '
                        . 'pozostaje chroniona.',
                    'Do zgłoszenia możesz dołączyć dokumenty (PDF, DOC, DOCX, JPG, PNG). Załączniki są '
                        . 'przechowywane w sposób bezpieczny i nie są publicznie dostępne.',
                ],
        ];
    }

    // Posts to the whistleblower REST endpoint (WhistleblowerServiceProvider); message body is plain text.
    private function form(bool $isEn): array
    {
        return [
            'endpoint' => rest_url('arpi/v1/report'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'lang'     => $isEn ? 'en' : 'pl',
            'heading'  => $isEn ? 'Submit a report' : 'Wyślij zgłoszenie',
            'labels'   => [
                'title'        => $isEn ? 'Report title' : 'Tytuł zgłoszenia',
                'message'      => $isEn ? 'What happened' : 'Treść zgłoszenia',
                'contact'      => $isEn ? 'Contact e-mail (optional)' : 'E-mail do kontaktu (opcjonalnie)',
                'contact_hint' => $isEn
                    ? 'Leave blank to report anonymously.'
                    : 'Zostaw puste, aby zgłosić anonimowo.',
                'attachments'  => $isEn ? 'Attachments' : 'Załączniki',
                'attachments_button' => $isEn ? 'Attach files...' : 'Załącz pliki...',
                'submit'       => $isEn ? 'Send report' : 'Wyślij zgłoszenie',
            ],
            'limits' => [
                'max_files' => 5,
                'max_mb'    => 10,
                'accept'    => '.pdf,.doc,.docx,.jpg,.jpeg,.png',
                'hint'      => $isEn
                    ? 'Up to 5 files, max 10 MB each · PDF, DOC, DOCX, JPG, PNG'
                    : 'Do 5 plików, maks. 10 MB każdy · PDF, DOC, DOCX, JPG, PNG',
            ],
            'consent' => $isEn
                ? 'I agree to the processing of the data in this report for the purpose of handling it.'
                : 'Wyrażam zgodę na przetwarzanie danych zawartych w zgłoszeniu w celu jego rozpatrzenia.',
            'success' => $isEn
                ? 'Thank you — your report has been received. If you left an e-mail, we will confirm shortly.'
                : 'Dziękujemy - zgłoszenie zostało przyjęte. Jeśli podałeś e-mail, wkrótce otrzymasz wiadomość potwierdzajacą.',
            'error' => $isEn
                ? 'Something went wrong. Please try again.'
                : 'Coś poszło nie tak. Spróbuj ponownie.',
            'errors' => [
                'max_files' => $isEn ? 'You can attach up to 5 files.' : 'Możesz dołączyć maksymalnie 5 plików.',
                'max_size'  => $isEn ? 'Each file must be under 10 MB.' : 'Każdy plik musi być mniejszy niż 10 MB.',
            ],
        ];
    }
}
