<?php

namespace App\Providers;

use App\Support\Icons;
use Illuminate\Support\ServiceProvider;

class AcfServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Populate every `icon_name` select (hero + scope) from the theme's
        // SVG icon folder, so the choice list tracks the files on disk.
        add_filter('acf/load_field/name=icon_name', function (array $field): array {
            $field['choices'] = Icons::choices();

            return $field;
        });
    }
}
