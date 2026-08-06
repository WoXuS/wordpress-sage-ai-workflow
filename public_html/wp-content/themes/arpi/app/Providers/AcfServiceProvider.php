<?php

namespace App\Providers;

use App\Support\Icons;
use Illuminate\Support\ServiceProvider;

class AcfServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Choices track the SVG files on disk.
        add_filter('acf/load_field/name=icon_name', function (array $field): array {
            $field['choices'] = Icons::choices();

            return $field;
        });

        add_action('acf/input/admin_enqueue_scripts', [$this, 'iconSelectThumbnails']);
    }

    // Extends ACF's Select2 rendering to prepend each icon's SVG thumbnail.
    public function iconSelectThumbnails(): void
    {
        $map = Icons::urlMap();

        if ($map === []) {
            return;
        }

        $js = <<<'JS'
(function () {
    if (typeof acf === 'undefined') return;
    var map = __MAP__;
    var render = function (option) {
        if (!option.id) return option.text;
        var $s = jQuery('<span class="arpi-icon-choice" style="display:inline-flex;align-items:center;gap:8px;"></span>');
        var url = map[option.id];
        if (url) {
            var $chip = jQuery('<span style="display:inline-flex;width:24px;height:24px;flex:0 0 auto;border-radius:4px;background:#f0f0f1;align-items:center;justify-content:center;"></span>');
            jQuery('<img/>', { src: url, alt: '' }).css({ width: '16px', height: '16px', objectFit: 'contain' }).appendTo($chip);
            $chip.appendTo($s);
        }
        $s.append(document.createTextNode(option.text));
        return $s;
    };
    acf.addFilter('select2_args', function (args, $select, settings, field) {
        if (!field) return args;
        var name = field.get('name') || '';
        var key = field.get('key') || '';
        if (name === 'icon_name' || key.indexOf('icon_name') !== -1) {
            args.templateResult = render;
            args.templateSelection = render;
        }
        return args;
    });
})();
JS;

        wp_add_inline_script('acf-input', str_replace('__MAP__', wp_json_encode($map), $js));
    }
}
