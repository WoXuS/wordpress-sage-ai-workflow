<?php

namespace App\View\Composers;

use App\Providers\ThemeSettingsServiceProvider;
use Illuminate\Support\Facades\Vite;
use Roots\Acorn\View\Composer;

class Footer extends Composer
{
    protected static $views = [
        'sections.footer',
    ];

    // Override with() so methods resolve eagerly: Acorn's Composer::merge() otherwise wraps
    // zero-arg public methods in InvokableComponentVariable, breaking array access in Blade.
    protected function with(): array
    {
        $content = $this->fromAcf() ?? $this->fromHardcoded();

        return [
            'company'    => $content['company'],
            'offices'    => $content['offices'],
            'socials'    => $content['socials'],
            'badges'     => $content['badges'],
            'newsletter' => $this->newsletter($content['newsletter'] ?? []),
            'whistleblower' => $this->whistleblower(),
        ];
    }

    protected function whistleblower(): array
    {
        $isEn = (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';
        $slug = $isEn ? 'whistleblower' : 'sygnalista';
        $page = get_page_by_path($slug);

        return [
            'url'   => $page ? get_permalink($page) : home_url('/'.$slug),
            'label' => $isEn ? 'whistleblower form' : 'zgłoszenie anonimowe',
        ];
    }

    // Reads the per-language theme-settings options page; null until company name filled, then hardcoded fallback renders.
    private function fromAcf(): ?array
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $id = ThemeSettingsServiceProvider::OPTIONS_ID;
        $company = get_field('company', $id);

        if (! $company || empty($company['name'])) {
            return null;
        }

        return [
            'company' => [
                'name'    => $company['name'] ?? '',
                'krs'     => $company['krs'] ?? '',
                'nip'     => $company['nip'] ?? '',
                'address' => $this->lines($company['address'] ?? ''),
            ],
            'offices' => array_map(fn ($row) => [
                'name'     => $row['name'] ?? '',
                'address'  => $this->lines($row['address'] ?? ''),
                'maps_url' => $row['maps_url'] ?? '',
                'phone'    => $row['phone'] ?? '',
                'email'    => $row['email'] ?? '',
            ], get_field('offices', $id) ?: []),
            'socials' => array_map(fn ($row) => [
                'network' => $row['network'] ?? '',
                'url'     => $row['url'] ?? '#',
                'icon'    => $row['icon_name'] ?? '',
            ], get_field('socials', $id) ?: []),
            'badges' => array_values(array_filter(array_map(function ($row) {
                $image = $row['image'] ?? null;

                return is_array($image) && ! empty($image['url'])
                    ? ['src' => $image['url'], 'alt' => $row['alt'] ?? '']
                    : null;
            }, get_field('badges', $id) ?: []))),
            'newsletter' => get_field('newsletter', $id) ?: [],
        ];
    }

    private function lines(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));
    }

    private function fromHardcoded(): array
    {
        return [
            'company'    => $this->company(),
            'offices'    => $this->offices(),
            'socials'    => $this->socials(),
            'badges'     => $this->badges(),
            'newsletter' => [],
        ];
    }

    protected function company(): array
    {
        return [
            'name' => 'ARPI & Partners Sp. z o.o.',
            'krs' => '0000255170',
            'nip' => '5213382100',
            'address' => ['Puławska 182', '02-670 Warszawa'],
        ];
    }

    protected function offices(): array
    {
        return [
            [
                'name' => 'Warszawa',
                'address' => ['Puławska 182', '02-670'],
                'maps_url' => 'https://maps.app.goo.gl/FyvPhPEQowJeBnW26',
                'phone' => '+48 22 559 00 55',
                'email' => 'contact@arpiaccounting.com',
            ],
            [
                'name' => 'Rzeszów',
                'address' => ['Juliusza Słowackiego 6/12', '35-060'],
                'maps_url' => 'https://maps.app.goo.gl/C792GtwKiCwuzbPc8',
                'phone' => '+48 538 235 852',
                'email' => 'contact@arpiaccounting.com',
            ],
        ];
    }

    // Posts to ContactServiceProvider → Newsletter PL/EN MailPoet list. ACF copy overrides win; endpoint/nonce/status stay theme-owned.
    protected function newsletter(array $acf = []): array
    {
        $isEn = (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';

        $defaults = [
            'heading' => 'Newsletter',
            'copy'    => 'Zapisz się do naszego newslettera i bądź na bieżąco z najważniejszymi zmianami w polskim prawie',
            'submit'  => 'Subskrybuj',
        ];

        $overrides = array_filter([
            'heading' => $acf['heading'] ?? null,
            'copy'    => $acf['copy'] ?? null,
            'submit'  => $acf['submit'] ?? null,
        ], fn ($v) => ! empty($v));

        return array_merge($defaults, $overrides, [
            'endpoint' => rest_url('arpi/v1/newsletter'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'lang'     => $isEn ? 'en' : 'pl',
            'success'  => $isEn ? 'Thank you for subscribing!' : 'Dziękujemy za zapisanie się!',
            'error'    => $isEn ? 'Something went wrong. Please try again.' : 'Coś poszło nie tak. Spróbuj ponownie.',
        ]);
    }

    protected function socials(): array
    {
        return [
            ['network' => 'Facebook', 'url' => 'https://www.facebook.com/arpiaccounting/', 'icon' => 'facebook'],
            ['network' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/arpi-accounting/', 'icon' => 'linkedin'],
        ];
    }

    protected function badges(): array
    {
        return [
            ['src' => Vite::asset('resources/images/forbes-diament.png'), 'alt' => 'Diamenty Forbes 2026'],
            ['src' => Vite::asset('resources/images/forbes-laureat.png'), 'alt' => 'Forbes Laureat 2026'],
        ];
    }
}
