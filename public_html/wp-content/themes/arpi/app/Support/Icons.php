<?php

namespace App\Support;

class Icons
{
    /**
     * SVG icons available in the theme, as ACF select choices
     * (slug => human label). Source of truth is resources/icons/*.svg.
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (glob(get_theme_file_path('resources/icons/*.svg')) as $file) {
            $slug = basename($file, '.svg');
            $choices[$slug] = ucfirst(str_replace('-', ' ', $slug));
        }

        return $choices;
    }
}
