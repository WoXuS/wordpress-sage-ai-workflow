<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class App extends Composer
{
    protected static $views = [
        '*',
    ];

    public function siteName(): string
    {
        return get_bloginfo('name', 'display');
    }
}
