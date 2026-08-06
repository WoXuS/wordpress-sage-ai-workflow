<?php

namespace App\Support;

class Icons
{
    /** Icons as ACF select choices (slug => human label); source: resources/icons/*.svg. */
    public static function choices(): array
    {
        $choices = [];

        foreach (glob(get_theme_file_path('resources/icons/*.svg')) as $file) {
            $slug = basename($file, '.svg');
            $choices[$slug] = ucfirst(str_replace('-', ' ', $slug));
        }

        return $choices;
    }

    /** slug => SVG URL, for thumbnails beside the labels in the ACF `icon_name` select. */
    public static function urlMap(): array
    {
        $map = [];

        foreach (glob(get_theme_file_path('resources/icons/*.svg')) as $file) {
            $slug = basename($file, '.svg');
            $map[$slug] = get_theme_file_uri("resources/icons/{$slug}.svg");
        }

        return $map;
    }
}
