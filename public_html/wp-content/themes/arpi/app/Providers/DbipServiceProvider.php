<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DbipServiceProvider extends ServiceProvider
{
    /** Top-level admin menu slug of the dbip-chapters CPT (parent for reorder + settings). */
    private const string CPT_MENU = 'edit.php?post_type=dbip-chapters';

    /** Term meta holding each chapter's manual sort position (seeded from legacy term_order). */
    private const string ORDER_META = 'dbip_chapter_order';

    public function boot(): void
    {
        add_action('acf/init', function () {
            if (! function_exists('acf_add_options_page')) {
                return;
            }

            acf_add_options_page([
                'page_title' => 'Doing Business in Poland',
                'menu_title' => 'Ustawienia DBiP',
                'menu_slug' => 'dbip-settings',
                'parent_slug' => self::CPT_MENU,
                'capability' => 'manage_options',
                'update_button' => 'Zapisz',
            ]);
        });

        // dbip-chapters is its own top-level menu, so WP shows the chapter-name
        // taxonomy natively as a submenu. Only the custom reorder screen needs to be
        // attached — nested under the DBiP CPT menu, alongside the ACF settings page.
        add_action('admin_menu', function () {
            add_submenu_page(
                self::CPT_MENU,
                'Kolejność rozdziałów DBiP',
                'Kolejność rozdziałów',
                'manage_categories',
                'dbip-chapter-order',
                [$this, 'renderChapterOrderPage']
            );
        });

        // Drag-and-drop reorder saves via admin-ajax (replaces the legacy plugin).
        add_action('wp_ajax_dbip_reorder_chapters', [$this, 'ajaxReorderChapters']);

        // DBiP is English-only — drop WordPress's Polish "Zabezpieczone:" prefix
        // on protected chapter titles (we show our own English notice instead).
        add_filter('protected_title_format', function ($format, $post) {
            return ($post->post_type ?? '') === 'dbip-chapters' ? '%s' : $format;
        }, 10, 2);
    }

    /** Renders the drag-and-drop screen for ordering chapter (chapter-name) terms. */
    public function renderChapterOrderPage(): void
    {
        if (! current_user_can('manage_categories')) {
            return;
        }

        $terms = get_terms(['taxonomy' => 'chapter-name', 'hide_empty' => false]);
        $terms = is_array($terms)
            ? array_filter($terms, fn ($t) => $t->slug !== 'no-chapter')
            : [];
        // Sort by the stored order; terms without a value fall to the top (position 0).
        usort($terms, fn ($a, $b) => (int) get_term_meta($a->term_id, self::ORDER_META, true)
            <=> (int) get_term_meta($b->term_id, self::ORDER_META, true));

        wp_enqueue_script('jquery-ui-sortable');
        $nonce = wp_create_nonce('dbip-reorder');

        echo '<div class="wrap"><h1>Kolejność rozdziałów DBiP</h1>';
        echo '<p>Przeciągnij rozdziały, aby ustawić kolejność. Zapis następuje automatycznie.</p>';
        echo '<p id="dbip-order-status" style="min-height:1.5em;font-weight:600;color:#2271b1;"></p>';

        if (empty($terms)) {
            echo '<p><em>Brak rozdziałów do posortowania.</em></p></div>';

            return;
        }

        echo '<ol id="dbip-chapter-order" style="max-width:640px;margin:0;padding:0;list-style:none;">';
        foreach ($terms as $t) {
            printf(
                '<li data-term-id="%d" style="display:flex;align-items:center;gap:8px;padding:10px 12px;'
                .'margin:0 0 6px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;cursor:move;">'
                .'<span class="dashicons dashicons-menu" style="color:#8c8f94;"></span>'
                .'<span>%s</span></li>',
                $t->term_id,
                esc_html(html_entity_decode($t->name, ENT_QUOTES))
            );
        }
        echo '</ol></div>';

        printf(
            '<script>jQuery(function($){'
            .'var l=$("#dbip-chapter-order"),s=$("#dbip-order-status");'
            .'l.sortable({axis:"y",update:function(){'
            .'var o=l.children("li").map(function(){return $(this).data("term-id");}).get();'
            .'s.text("Zapisywanie…");'
            .'$.post(ajaxurl,{action:"dbip_reorder_chapters",nonce:%1$s,order:o},function(r){'
            .'s.text(r&&r.success?"Zapisano ✓":"Błąd zapisu");'
            .'}).fail(function(){s.text("Błąd zapisu");});'
            .'}});});</script>',
            wp_json_encode($nonce)
        );
    }

    /** Persists the new chapter order (term_id list) into ORDER_META, 1-based. */
    public function ajaxReorderChapters(): void
    {
        check_ajax_referer('dbip-reorder', 'nonce');

        if (! current_user_can('manage_categories')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        $order = isset($_POST['order']) && is_array($_POST['order'])
            ? array_map('intval', $_POST['order'])
            : [];

        $position = 1;
        foreach ($order as $termId) {
            if ($termId > 0) {
                update_term_meta($termId, self::ORDER_META, $position++);
            }
        }

        wp_send_json_success(['count' => count($order)]);
    }
}
