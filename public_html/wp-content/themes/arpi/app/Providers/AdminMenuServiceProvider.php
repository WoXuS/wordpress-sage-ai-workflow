<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Groups all custom ARPI content under a single "ARPI" parent in the wp-admin
 * sidebar. The CPTs (usluga, dbip-chapters, zgloszenie, …) nest under it via
 * their `admin_menu_parent` (ACF post-type JSON) / `show_in_menu` set to
 * self::SLUG; options pages point their `parent_slug` here too.
 */
class AdminMenuServiceProvider extends ServiceProvider
{
    public const SLUG = 'arpi-admin';

    public function boot(): void
    {
        // Priority 9 so the parent exists before the CPT `show_in_menu` items
        // (which core wires on the default admin_menu pass) render under it.
        add_action('admin_menu', function () {
            add_menu_page(
                'ARPI',
                'ARPI',
                'edit_posts',
                self::SLUG,
                [$this, 'landing'],
                'dashicons-screenoptions',
                26 // below Comments, above Appearance
            );
        }, 9);
    }

    /** Landing shown when the "ARPI" parent itself is clicked (first submenu). */
    public function landing(): void
    {
        echo '<div class="wrap"><h1>ARPI</h1>'
            .'<p>Treści i ustawienia ARPI. Wybierz sekcję z menu po lewej stronie.</p></div>';
    }
}
