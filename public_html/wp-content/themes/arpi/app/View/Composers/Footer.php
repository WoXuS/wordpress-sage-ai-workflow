<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Vite;
use Roots\Acorn\View\Composer;

class Footer extends Composer
{
    /**
     * Views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'sections.footer',
    ];

    /**
     * The data passed to the view before rendering.
     *
     * Acorn's Composer::merge() wraps zero-arg public methods in
     * Illuminate\View\InvokableComponentVariable when left to auto-extract,
     * which breaks direct array access (e.g. $company['name']) in Blade.
     * Overriding with() resolves the methods eagerly so views receive
     * plain arrays.
     */
    protected function with(): array
    {
        return [
            'company' => $this->company(),
            'offices' => $this->offices(),
            'newsletter' => $this->newsletter(),
            'socials' => $this->socials(),
            'badges' => $this->badges(),
        ];
    }

    /**
     * Company registration details.
     * TODO: swap to get_field(..., 'option') in the ACF phase.
     */
    public function company(): array
    {
        return [
            'name' => 'ARPI & Partners Sp. z o.o.',
            'krs' => '0000255170',
            'nip' => '5213382100',
            'address' => ['Puławska 182', '02-670 Warszawa'],
        ];
    }

    /**
     * Office locations.
     */
    public function offices(): array
    {
        return [
            [
                'name' => 'Rzeszów',
                'address' => ['Juliusza Słowackiego 6/12', '35-060'],
                'maps_url' => '#',
                'phone' => '+48 538 235 852',
                'email' => 'contact@arpiaccounting.com',
            ],
            [
                'name' => 'Warszawa',
                'address' => ['Puławska 182', '02-670'],
                'maps_url' => '#',
                'phone' => '+48 22 559 00 55',
                'email' => 'contact@arpiaccounting.com',
            ],
        ];
    }

    /**
     * Newsletter block copy (markup-only form; MailPoet wired later).
     */
    public function newsletter(): array
    {
        return [
            'heading' => 'Newsletter',
            'copy' => 'Zapisz się do naszego newslettera i bądź na bieżąco z najważniejszymi zmianami w polskim prawie',
            'submit' => 'Subskrybuj',
            'action' => '#',
        ];
    }

    /**
     * Social links (URLs are placeholders).
     */
    public function socials(): array
    {
        return [
            ['network' => 'Facebook', 'url' => '#', 'icon' => 'facebook'],
            ['network' => 'LinkedIn', 'url' => '#', 'icon' => 'linkedin'],
        ];
    }

    /**
     * Forbes badges.
     */
    public function badges(): array
    {
        return [
            ['src' => Vite::asset('resources/images/forbes-diament.png'), 'alt' => 'Diamenty Forbes 2026'],
            ['src' => Vite::asset('resources/images/forbes-laureat.png'), 'alt' => 'Forbes Laureat 2026'],
        ];
    }
}
