<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Term;

class Dbip extends Composer
{
    protected static $views = [
        'archive-dbip-chapters',
        'taxonomy-chapter-name',
        'single-dbip-chapters',
    ];

    public function with(): array
    {
        $shared = ['archiveUrl' => get_post_type_archive_link('dbip-chapters')];

        if (is_post_type_archive('dbip-chapters')) {
            return $shared + ['archive' => $this->archive()];
        }

        if (is_tax('chapter-name')) {
            return $shared + ['chapter' => $this->chapter()];
        }

        return $shared + ['single' => $this->single()];
    }

    // ---- Archive -----------------------------------------------------------

    private function archive(): array
    {
        $chapters = $this->orderedChapters();

        return [
            'version' => get_field('dbip_version', 'option') ?: 'v 1.25',
            'date' => get_field('dbip_date', 'option') ?: 'January 2025',
            'ceo' => $this->ceoCopy(),
            'about' => $this->aboutCopy(),
            'chapters' => array_map(fn (WP_Term $t, int $i) => [
                'number' => $i + 1,
                'name' => $this->decode($t->name),
                'description' => $t->description,
                'url' => get_term_link($t),
            ], $chapters, array_keys($chapters)),
            'glossary' => $this->glossaryTile(),
        ];
    }

    // ---- Taxonomy (chapter) ------------------------------------------------

    private function chapter(): array
    {
        $term = get_queried_object();
        $chapters = $this->orderedChapters();
        $number = $this->chapterNumber($term, $chapters);
        $image = get_field('title_image', 'term_'.$term->term_id);

        return [
            'number' => $number,
            'name' => $this->decode($term->name),
            'description' => $term->description,
            'intro' => get_field('chapter_introduction', 'term_'.$term->term_id) ?: '',
            'image' => is_array($image) ? $image['url'] : null,
            'breadcrumb' => [
                ['text' => 'Doing business in Poland', 'url' => $this->archiveUrl()],
                ['prefix' => $number.'.', 'text' => $this->decode($term->name)],
            ],
            'subchapters' => $this->subchapters($term),
        ];
    }

    private function subchapters(WP_Term $term): array
    {
        $posts = get_posts([
            'post_type' => 'dbip-chapters',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'tax_query' => [[
                'taxonomy' => 'chapter-name',
                'field' => 'term_id',
                'terms' => $term->term_id,
            ]],
        ]);

        return array_map(fn ($p, $i) => [
            'letter' => chr(97 + $i),
            'title' => $this->decode($p->post_title),
            'url' => get_permalink($p),
            'excerpt' => wp_trim_words(wp_strip_all_tags($p->post_content), 40, '…'),
            'locked' => post_password_required($p),
        ], $posts, array_keys($posts));
    }

    // ---- Single ------------------------------------------------------------

    private function single(): array
    {
        $post = get_post();
        $terms = get_the_terms($post, 'chapter-name');
        $term = ($terms && ! is_wp_error($terms)) ? $terms[0] : null;
        $isGlossary = ! $term || $term->slug === 'no-chapter';

        if ($isGlossary) {
            return [
                'is_glossary' => true,
                'title' => $this->decode(get_the_title()),
                'breadcrumb' => [
                    ['text' => 'Doing business in Poland', 'url' => $this->archiveUrl()],
                    ['text' => $this->decode(get_the_title())],
                ],
                'nav' => ['prev' => null, 'next' => null],
            ];
        }

        $chapters = $this->orderedChapters();
        $number = $this->chapterNumber($term, $chapters);
        $siblings = get_posts([
            'post_type' => 'dbip-chapters',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'tax_query' => [[
                'taxonomy' => 'chapter-name',
                'field' => 'term_id',
                'terms' => $term->term_id,
            ]],
        ]);
        $ids = wp_list_pluck($siblings, 'ID');
        $pos = array_search($post->ID, $ids, true);
        $letter = chr(97 + (int) $pos);

        return [
            'is_glossary' => false,
            'title' => $this->decode(get_the_title()),
            'breadcrumb' => [
                ['text' => 'Doing business in Poland', 'url' => $this->archiveUrl()],
                ['prefix' => $number.'.', 'text' => $this->decode($term->name), 'url' => get_term_link($term)],
                ['prefix' => $letter.'.', 'text' => $this->decode(get_the_title())],
            ],
            'nav' => [
                'prev' => $this->adjacent($siblings, $pos, -1, $chapters, $number, 'prev'),
                'next' => $this->adjacent($siblings, $pos, +1, $chapters, $number, 'next'),
            ],
        ];
    }

    // Previous/next subchapter, or the neighbouring chapter at a boundary.
    private function adjacent(array $siblings, int $pos, int $step, array $chapters, int $number, string $dir): ?array
    {
        if (isset($siblings[$pos + $step])) {
            $p = $siblings[$pos + $step];

            return [
                'prefix' => chr(97 + $pos + $step).'.',
                'title' => $this->decode($p->post_title),
                'url' => get_permalink($p),
                'is_chapter' => false,
            ];
        }

        $target = $chapters[($number - 1) + $step] ?? null;
        if (! $target) {
            return null;
        }

        $targetNumber = $this->chapterNumber($target, $chapters);

        return [
            'prefix' => $dir === 'prev' ? 'Previous Chapter' : 'Next Chapter',
            'title' => $targetNumber.'. '.$this->decode($target->name),
            'url' => get_term_link($target),
            'is_chapter' => true,
        ];
    }

    // ---- Shared helpers ----------------------------------------------------

    /** @return WP_Term[] ordered by dbip_chapter_order, no-chapter excluded */
    private function orderedChapters(): array
    {
        $terms = get_terms([
            'taxonomy' => 'chapter-name',
            'hide_empty' => false,
            'meta_key' => 'dbip_chapter_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
        ]);

        return ($terms && ! is_wp_error($terms))
            ? array_values(array_filter($terms, fn ($t) => $t->slug !== 'no-chapter'))
            : [];
    }

    private function chapterNumber(WP_Term $term, array $chapters): int
    {
        foreach ($chapters as $i => $t) {
            if ($t->term_id === $term->term_id) {
                return $i + 1;
            }
        }

        return 0;
    }

    private function glossaryTile(): ?array
    {
        $posts = get_posts([
            'post_type' => 'dbip-chapters',
            'numberposts' => 1,
            'tax_query' => [[
                'taxonomy' => 'chapter-name',
                'field' => 'slug',
                'terms' => 'no-chapter',
            ]],
        ]);

        if (! $posts) {
            return null;
        }

        return [
            'title' => $this->decode($posts[0]->post_title),
            'url' => get_permalink($posts[0]),
            'excerpt' => wp_trim_words(wp_strip_all_tags($posts[0]->post_content), 30, '…'),
        ];
    }

    private function archiveUrl(): string
    {
        return get_post_type_archive_link('dbip-chapters');
    }

    private function decode(string $s): string
    {
        return html_entity_decode($s, ENT_QUOTES);
    }

    private function ceoCopy(): array
    {
        return [
            'heading' => 'The perspective of our CEO',
            'author' => 'Jan Prejsnar',
            'role' => 'CEO',
            'paragraphs' => [
                'If you are reading this, you are probably thinking about starting your business in Poland. A beautiful country with a fast-growing economy, open democratic society, and investor-friendly legal environment. However, a lot has changed since 2020, when we published the first edition of Doing Business in Poland. The Polish tax system has been rearranged almost beyond recognition within a few years, and recently due to the controversial revision of the financial law, commonly referred to as the Polish Deal.',
                'As we can see, the new regulations have already had a significant impact on the profits of companies and home budgets of citizens, but also pose a challenge for entrepreneurs who plan to start their business activity in Poland. Whether you’re planning to open a branch of your company in Poland or start a brand new business, it is essential to be familiar with Poland’s corporate environment, tax system, accounting and employment procedures as well as new regulations. Informing about those changes and pointing the way for foreign entrepreneurs is therefore our priority.',
                'Back in 2001, the owners of ARPI Group found themselves in a similar situation. We have faced many challenges in establishing a new company in Poland. It was a demanding, nonetheless extremely rewarding experience. Take advantage of our extensive knowledge, especially today, in times of frequent changes in tax and business law. Everything you need to know about accounting, employment, tax obligations, and legal aspects, is within your reach with Doing Business in Poland.',
                'The new, revised version of Doing Business in Poland covers everything that is in force as of January 2025 – including every Polish Deal amendments, which came into force in 2023. Our guide will also present you with the best way to achieve your business goals and, most importantly, we hope it will support you in the process of establishing yourself as a successful entrepreneur.',
            ],
        ];
    }

    private function aboutCopy(): array
    {
        return [
            'heading' => 'About us',
            'paragraphs' => [
                'ARPI Accounting is a well-established outsourcing company that offers a variety of financial services for medium and large enterprises. We are a member of ARPI Group founded in Norway in 1999. Our Polish-Norwegian connection and international perspective allow us to fully understand a complex Polish financial system that is subject to constant changes.',
                'We are a Warsaw-based, versatile accounting office that constantly searches for new ways of supporting our clients. This publication is just one example of our pro-active approach. We also provide original high-end software solutions that help with everyday accounting. On top of that, we run a specialized blog in which we explain the latest changes in Polish tax law. Our dedication is boundless.',
                'We are proud to employ experienced and fully qualified expert accountants, in-house lawyers, HR and payroll specialists, who are always ready to help. If you only need advice, you can be sure that our tax advisors will dispel doubts regarding year-round bookkeeping, as well as year-end preparations.',
            ],
            'cta' => ['label' => 'arpiaccounting.com', 'url' => 'https://arpiaccounting.com'],
        ];
    }
}
