<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Contact extends Composer
{
    protected static $views = ['template-contact'];

    protected function with(): array
    {
        $isEn = $this->isEn();

        return [
            'hero'    => $this->hero($isEn),
            'general' => $this->general($isEn),
            'offices' => $this->offices($isEn),
            'socials' => $this->socials(),
            'form'    => $this->form($isEn),
        ];
    }

    private function isEn(): bool
    {
        return (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';
    }

    private function hero(bool $isEn): array
    {
        return [
            'title'  => $isEn ? 'Talk to' : 'Porozmawiaj',
            'accent' => $isEn ? 'ARPI' : 'z ARPI',
            'lead'   => $isEn
                ? 'Tell us about your business and we will get back to you with the right solution. '
                    . 'Fill in the form or reach us directly at one of our offices.'
                : 'Opowiedz nam o swoim biznesie, a my wrócimy do Ciebie z odpowiednim rozwiązaniem. '
                    . 'Wypełnij formularz lub skontaktuj się bezpośrednio z jednym z naszych biur.',
        ];
    }

    /**
     * Firm-wide contact channel (shown above the office list).
     */
    private function general(bool $isEn): array
    {
        return [
            'heading' => $isEn ? 'General enquiries' : 'Zapytania ogólne',
            'email'   => 'contact@arpiaccounting.com',
            'phone'   => '+48 22 559 00 55',
        ];
    }

    /**
     * Office locations. Mirrors Footer::offices() plus opening hours.
     * TODO: swap to get_field(..., 'option') in the ACF phase.
     */
    private function offices(bool $isEn): array
    {
        $hours = $isEn ? 'Mon–Fri, 8:00–16:00' : 'pon.–pt., 8:00–16:00';

        return [
            [
                'name'     => 'Warszawa',
                'address'  => ['Puławska 182', '02-670 Warszawa'],
                'maps_url' => 'https://maps.app.goo.gl/FyvPhPEQowJeBnW26',
                'phone'    => '+48 22 559 00 55',
                'email'    => 'contact@arpiaccounting.com',
                'hours'    => $hours,
            ],
            [
                'name'     => 'Rzeszów',
                'address'  => ['Juliusza Słowackiego 6/12', '35-060 Rzeszów'],
                'maps_url' => 'https://maps.app.goo.gl/C792GtwKiCwuzbPc8',
                'phone'    => '+48 538 235 852',
                'email'    => 'contact@arpiaccounting.com',
                'hours'    => $hours,
            ],
        ];
    }

    private function socials(): array
    {
        return [
            ['network' => 'Facebook', 'url' => 'https://www.facebook.com/arpiaccounting/', 'icon' => 'facebook'],
            ['network' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/arpi-accounting/', 'icon' => 'linkedin'],
        ];
    }

    /**
     * Contact form copy + field labels. Values feed the AJAX endpoint, so keep
     * the option `value`s stable (language-independent) while labels branch.
     */
    private function form(bool $isEn): array
    {
        return [
            'endpoint' => rest_url('arpi/v1/contact'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'lang'     => $isEn ? 'en' : 'pl',
            'heading'  => $isEn ? 'Send us a message' : 'Napisz do nas',
            'labels'   => [
                'name'    => $isEn ? 'Full name' : 'Imię i nazwisko',
                'email'   => $isEn ? 'Email address' : 'Adres e-mail',
                'phone'   => $isEn ? 'Phone (optional)' : 'Telefon (opcjonalnie)',
                'company' => $isEn ? 'Company (optional)' : 'Firma (opcjonalnie)',
                'topic'   => $isEn ? 'Topic' : 'Temat',
                'message' => $isEn ? 'Message' : 'Wiadomość',
                'submit'  => $isEn ? 'Send message' : 'Wyślij wiadomość',
            ],
            'topics' => [
                ['value' => 'accounting',  'label' => $isEn ? 'Accounting' : 'Księgowość'],
                ['value' => 'hr-payroll',  'label' => $isEn ? 'HR & Payroll' : 'Kadry i płace'],
                ['value' => 'tax',         'label' => $isEn ? 'Tax advisory' : 'Doradztwo podatkowe'],
                ['value' => 'law',         'label' => $isEn ? 'Law' : 'Prawo'],
                ['value' => 'other',       'label' => $isEn ? 'Other' : 'Inne'],
            ],
            'consent' => $isEn
                ? 'I agree to the processing of my personal data in order to respond to my enquiry.'
                : 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu odpowiedzi na zapytanie.',
            'newsletter' => $isEn
                ? 'I want to receive the ARPI newsletter with tax and accounting updates.'
                : 'Chcę otrzymywać newsletter ARPI ze zmianami w podatkach i księgowości.',
            'success' => $isEn
                ? 'Thank you — your message has been sent. We will get back to you shortly.'
                : 'Dziękujemy — wiadomość została wysłana. Odezwiemy się wkrótce.',
            'error' => $isEn
                ? 'Something went wrong. Please try again or email us directly.'
                : 'Coś poszło nie tak. Spróbuj ponownie lub napisz do nas bezpośrednio.',
        ];
    }
}
