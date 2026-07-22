<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Term;

class Blog extends Composer
{
    protected static $views = [
        'index',
        'partials.content-single-post',
    ];

    public function with(): array
    {
        return $this->view->name() === 'index'
            ? $this->archive()
            : $this->single();
    }

    // ---- Archive (blog home, category / tag / author / date) ---------------

    private function archive(): array
    {
        $isEn = $this->isEn();
        $activeTermId = null;
        $intro = null;

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $activeTermId = $term->term_id ?? null;
            $heading = $this->decode($term->name);
            $intro = $term->description ? $this->decode($term->description) : null;
        } elseif (is_author()) {
            $heading = get_the_author();
        } elseif (is_date()) {
            $heading = wp_strip_all_tags(get_the_archive_title());
        } else {
            $heading = 'Blog';
            $intro = $isEn
                ? 'Follow the latest developments in tax and accounting. Every article is prepared '
                    .'on a regular basis by the team of experts at ARPI.'
                : 'Zapraszamy do śledzenia najnowszych zmian dotyczących podatków i księgowości. '
                    .'Wszystkie artykuły są regularnie opracowywane przez zespół ekspertów ARPI.';
        }

        return [
            'heading' => $heading,
            'intro' => $intro,
            'categories' => $this->categories(),
            'activeTermId' => $activeTermId,
            'allUrl' => $this->blogUrl(),
            'allLabel' => $isEn ? 'All articles' : 'Wszystkie artykuły',
            'empty' => $isEn ? 'The latest articles will appear here soon.' : 'Wkrótce pojawią się tu najnowsze artykuły.',
            'pagination' => $this->pagination(),
        ];
    }

    /** @return WP_Term[] current-language categories, "Uncategorized" excluded */
    private function categories(): array
    {
        $excluded = [(int) get_option('default_category')];
        if (function_exists('pll_get_term_translations')) {
            $excluded = array_map('intval', pll_get_term_translations($excluded[0]));
        }

        $categories = get_categories(['hide_empty' => true, 'exclude' => $excluded]);

        if (function_exists('pll_current_language') && function_exists('pll_get_term_language')) {
            $lang = pll_current_language();
            $categories = array_values(array_filter(
                $categories,
                fn ($cat) => pll_get_term_language($cat->term_id) === $lang
            ));
        }

        return $categories;
    }

    /** @return string[]|null paginate_links() anchors, styled via .c-pagination */
    private function pagination(): ?array
    {
        global $wp_query;

        $links = paginate_links([
            'total' => $wp_query->max_num_pages,
            'type' => 'array',
            'prev_text' => '‹',
            'next_text' => '›',
            'mid_size' => 1,
            'end_size' => 1,
        ]);

        return $links ?: null;
    }

    // ---- Single post -------------------------------------------------------

    private function single(): array
    {
        $isEn = $this->isEn();
        $category = $this->primaryCategory();
        $title = $this->decode(get_the_title());

        $crumbs = [['text' => 'Blog', 'url' => $this->blogUrl()]];
        if ($category) {
            $crumbs[] = ['text' => $this->decode($category->name), 'url' => get_category_link($category)];
        }
        $crumbs[] = ['text' => $title];

        return [
            'crumbs' => $crumbs,
            'category' => $category,
            'meta' => [
                'date' => get_the_date(),
                'author' => get_the_author(),
            ],
            'nav' => [
                'prev' => $this->adjacent(false, $isEn),
                'next' => $this->adjacent(true, $isEn),
            ],
            'labels' => [
                'by' => $isEn ? 'By' : 'Autor',
            ],
        ];
    }

    private function adjacent(bool $next, bool $isEn): ?array
    {
        $post = $next ? get_next_post() : get_previous_post();
        if (! $post) {
            return null;
        }

        return [
            'url' => get_permalink($post),
            'title' => $this->decode(get_the_title($post)),
            'prefix' => $next
                ? ($isEn ? 'Next article' : 'Następny artykuł')
                : ($isEn ? 'Previous article' : 'Poprzedni artykuł'),
            'is_chapter' => true, // renders the two-line label + title layout in x-dbip.nav-link
        ];
    }

    private function primaryCategory(): ?WP_Term
    {
        $default = (int) get_option('default_category');
        $categories = get_the_category();

        foreach ($categories as $cat) {
            if ($cat->term_id !== $default) {
                return $cat;
            }
        }

        return null;
    }

    // ---- Shared helpers ----------------------------------------------------

    private function blogUrl(): string
    {
        $postsPage = (int) get_option('page_for_posts');

        return $postsPage ? get_permalink($postsPage) : home_url('/blog');
    }

    private function isEn(): bool
    {
        return (function_exists('pll_current_language') ? pll_current_language() : null) === 'en';
    }

    private function decode(string $s): string
    {
        return html_entity_decode($s, ENT_QUOTES);
    }
}
