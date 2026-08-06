<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * "ARPI" is a lightweight hub at the top of the wp-admin sidebar. The custom
 * domains (dbip-chapters, usluga, zgloszenie) stay as their own top-level menus
 * — WP only supports two menu levels, so each domain keeps its full native
 * submenu (Add New, taxonomies, reorder, settings). This provider just clusters
 * ARPI + those domains together at the top and adds a subtle gap separating the
 * custom block from the rest of the admin menu.
 */
class AdminMenuServiceProvider extends ServiceProvider
{
    public const string SLUG = 'arpi-admin';

    /**
     * Top-level menu slugs that form the ARPI block, in display order. Kept
     * contiguous right below the Dashboard; the last one that actually exists
     * gets the separating margin. `theme-settings` only exists with ACF Pro.
     */
    private const array GROUP = [
        self::SLUG,
        'edit.php?post_type=dbip-chapters',
        'edit.php?post_type=usluga',
        'edit.php?post_type=zgloszenie',
        'theme-settings',
    ];

    public function boot(): void
    {
        add_action('admin_menu', function () {
            add_menu_page(
                'ARPI',
                'ARPI',
                'edit_posts',
                self::SLUG,
                [$this, 'landing'],
                'none', // icon drawn via CSS mask in menuIcon() so it follows the color scheme
                2 // below Kokpit
            );
        }, 9);

        // Force the ARPI block to sit together at the top of the menu.
        add_filter('custom_menu_order', '__return_true');
        add_filter('menu_order', [$this, 'clusterMenu']);

        add_action('admin_head', [$this, 'menuIcon']);
        add_action('admin_head', [$this, 'groupStyle']);
    }

    /** Reorders the top-level menu so the ARPI block is contiguous right after the Dashboard. */
    public function clusterMenu(array $order): array
    {
        $rest = array_values(array_diff($order, self::GROUP));
        $dashboard = array_search('index.php', $rest, true);
        $insertAt = $dashboard === false ? 0 : $dashboard + 1;

        array_splice($rest, $insertAt, 0, self::GROUP);

        return $rest;
    }

    /**
     * Paints the "A" logo into the menu icon slot as a CSS mask filled with
     * `currentColor`. Because wp-admin already sets that color on
     * `div.wp-menu-image:before` per scheme and state, the icon inherits the
     * exact resting/hover/current colors of native dashicons on every scheme.
     */
    public function menuIcon(): void
    {
        $uri = get_theme_file_uri('resources/images/a-icon.svg');
        printf(
            '<style id="arpi-admin-menu-icon">'
            .'#adminmenu #toplevel_page_%1$s div.wp-menu-image:before{'
            .'content:"";display:block;width:36px;height:34px;padding:0;'
            .'background-color:currentColor;'
            .'-webkit-mask:url(%2$s) no-repeat center;mask:url(%2$s) no-repeat center;'
            .'-webkit-mask-size:20px auto;mask-size:20px auto;'
            .'}</style>',
            esc_attr(self::SLUG),
            esc_url($uri)
        );
    }

    /**
     * Styles the ARPI block as one cohesive section: a brand-tinted background with
     * a left accent bar (logo colour #952d58), rounded top/bottom to read as a card,
     * and a margin below it separating the block from the standard WP menu. Each
     * `<li>` id is read straight from $menu[$i][5] (whatever WP rendered), so it
     * works for both the add_menu_page hub and the CPT menus, and only styles the
     * items that actually exist (theme/dbip settings appear only with ACF Pro).
     */
    public function groupStyle(): void
    {
        global $menu;

        if (! is_array($menu)) {
            return;
        }

        $idBySlug = [];
        foreach ($menu as $item) {
            if (isset($item[2], $item[5])) {
                $idBySlug[$item[2]] = preg_replace('|[^a-zA-Z0-9_:.]|', '-', $item[5]);
            }
        }

        $ids = [];
        foreach (self::GROUP as $slug) {
            if (isset($idBySlug[$slug])) {
                $ids[] = $idBySlug[$slug];
            }
        }

        if ($ids === []) {
            return;
        }

        $block = '#adminmenu #'.implode(',#adminmenu #', $ids);
        // The top-level <a> and the expanded submenu each paint their own opaque
        // background above the <li>, so repeat the accent bar on both to keep it
        // continuous through every state and all the way down an open submenu.
        $blockLinks = '#adminmenu #'.implode(' > a.menu-top,#adminmenu #', $ids).' > a.menu-top';
        $blockSub = '#adminmenu #'.implode(' .wp-submenu,#adminmenu #', $ids).' .wp-submenu';
        $first = $ids[0];
        $last = end($ids);

        // Open item (WP marks it .wp-menu-open / .wp-has-current-submenu): same bar
        // width, just a lighter accent so the currently expanded section stands out.
        $open = [];
        foreach ($ids as $id) {
            foreach (['.wp-menu-open', '.wp-has-current-submenu'] as $state) {
                $open[] = "#adminmenu #{$id}{$state}";
                $open[] = "#adminmenu #{$id}{$state} > a.menu-top";
                $open[] = "#adminmenu #{$id}{$state} .wp-submenu";
            }
        }

        echo '<style id="arpi-admin-group">'
            .$block.'{background:rgba(149,45,88,.13);box-shadow:inset 3px 0 0 #952d58;}'
            .$blockLinks.',' .$blockSub.'{box-shadow:inset 3px 0 0 #952d58;}'
            .implode(',', $open).'{box-shadow:inset 3px 0 0 #c9527d;}'
            .'#adminmenu #'.$first.'{border-top-left-radius:6px;border-top-right-radius:6px;}'
            .'#adminmenu #'.$last.'{border-bottom-left-radius:6px;border-bottom-right-radius:6px;}'
            .'</style>';
    }

    /** Hub shown when "ARPI" is clicked: brand-accented cards linking every custom section. */
    public function landing(): void
    {
        $sections = [
            'Doing Business in Poland' => [
                ['Rozdziały', 'edit.php?post_type=dbip-chapters', 'dashicons-book-alt'],
                ['Nazwy rozdziałów', 'edit-tags.php?taxonomy=chapter-name&post_type=dbip-chapters', 'dashicons-category'],
                ['Kolejność rozdziałów', 'edit.php?post_type=dbip-chapters&page=dbip-chapter-order', 'dashicons-sort'],
            ],
            'Usługi' => [
                ['Wszystkie usługi', 'edit.php?post_type=usluga', 'dashicons-portfolio'],
            ],
            'Sygnalista' => [
                ['Zgłoszenia', 'edit.php?post_type=zgloszenie', 'dashicons-shield-alt'],
            ],
        ];

        // Options pages exist only with ACF Pro — show their cards only when available.
        if (function_exists('acf_add_options_page')) {
            $sections['Doing Business in Poland'][] =
                ['Ustawienia wersji', 'edit.php?post_type=dbip-chapters&page=dbip-settings', 'dashicons-admin-generic'];
            $sections['Ustawienia'] = [
                ['Ustawienia motywu', 'admin.php?page=theme-settings', 'dashicons-admin-appearance'],
            ];
        }

        echo '<div class="wrap"><h1>ARPI</h1>';
        echo '<p style="max-width:60ch;color:#50575e;">Centrum zarządzania treścią i ustawieniami ARPI. '
            .'Wybierz sekcję poniżej lub z menu po lewej stronie.</p>';

        foreach ($sections as $title => $links) {
            printf('<h2 style="margin:26px 0 10px;font-size:14px;text-transform:uppercase;letter-spacing:.04em;color:#646970;">%s</h2>', esc_html($title));
            echo '<div style="display:flex;flex-wrap:wrap;gap:12px;">';
            foreach ($links as [$label, $path, $icon]) {
                printf(
                    '<a href="%s" style="display:flex;align-items:center;gap:10px;min-width:230px;padding:14px 16px;'
                    .'background:#fff;border:1px solid #dcdcde;border-left:3px solid #952d58;border-radius:6px;'
                    .'text-decoration:none;color:#1d2327;font-weight:600;box-shadow:0 1px 1px rgba(0,0,0,.04);">'
                    .'<span class="dashicons %s" style="color:#952d58;flex:0 0 auto;"></span>%s</a>',
                    esc_url(admin_url($path)),
                    esc_attr($icon),
                    esc_html($label)
                );
            }
            echo '</div>';
        }

        echo '</div>';
    }
}
