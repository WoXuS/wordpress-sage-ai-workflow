<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Whistleblower ("Sygnalista") reporting channel.
 *
 * Backs the public report form: a REST route accepts a title, message, optional
 * reporter contact and file attachments, then (1) stores the report in a private
 * `zgloszenie` register CPT, (2) emails the handling committee, and (3) sends an
 * acknowledgment to the reporter when they left a contact address (whistleblower
 * law: confirm receipt within 7 days). The channel is anonymous-capable — no
 * identifying field is required.
 *
 * Confidentiality: attachments are stored under uploads/zgloszenia/ with random
 * file names, a directory `.htaccess` deny (Apache/LiteSpeed — cyberFolks) plus a
 * matching nginx deny (dev, etc/nginx/default.conf). Direct web access is blocked;
 * the committee downloads them only through an authenticated admin endpoint.
 * NOTE for go-live: for extra defence in depth, consider moving the storage dir
 * outside the web root on production. Turnstile is planned across all site forms.
 */
class WhistleblowerServiceProvider extends ServiceProvider
{
    private const SUBDIR = 'zgloszenia';

    private const MAX_FILES = 5;

    private const MAX_BYTES = 10 * 1024 * 1024; // 10 MB per file

    /** Allowed attachment types (extension => mime), mirrors the legacy channel. */
    private const ALLOWED = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];

    public function boot(): void
    {
        // Fallback registration so the intake works even if ACF (which registers
        // the CPT from local JSON) is inactive. No-op when ACF already did it.
        add_action('init', [$this, 'ensureRegisterCpt']);

        add_action('rest_api_init', function () {
            register_rest_route('arpi/v1', '/report', [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleReport'],
                'permission_callback' => '__return_true',
            ]);
        });

        // Admin: triage meta box, list columns, and the gated attachment download.
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post_zgloszenie', [$this, 'saveStatus']);
        add_filter('manage_zgloszenie_posts_columns', [$this, 'columns']);
        add_action('manage_zgloszenie_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        add_action('admin_post_arpi_wb_file', [$this, 'downloadAttachment']);
    }

    public function ensureRegisterCpt(): void
    {
        if (post_type_exists('zgloszenie')) {
            return;
        }

        register_post_type('zgloszenie', [
            'labels' => ['name' => 'Zgłoszenia (Sygnalista)', 'singular_name' => 'Zgłoszenie', 'menu_name' => 'Sygnalista'],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => AdminMenuServiceProvider::SLUG,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'has_archive' => false,
            'rewrite' => false,
            'show_in_rest' => false,
            'menu_icon' => 'dashicons-shield-alt',
            'menu_position' => 58,
            'supports' => ['title', 'editor'],
        ]);
    }

    // ---- Public submission ---------------------------------------------------

    public function handleReport(WP_REST_Request $request): WP_REST_Response
    {
        if ($drop = $this->guard($request)) {
            return $drop;
        }

        $isEn    = $request->get_param('lang') === 'en';
        $title   = sanitize_text_field((string) $request->get_param('title'));
        $message = wp_kses_post((string) $request->get_param('message'));
        $contact = sanitize_email((string) $request->get_param('contact'));
        $consent = $this->truthy($request->get_param('consent'));

        if ($title === '' || trim(wp_strip_all_tags($message)) === '' || ! $consent) {
            return $this->error($isEn ? 'Please fill in the required fields.' : 'Uzupełnij wymagane pola.');
        }

        $files = $this->collectFiles($request);
        if ($files instanceof WP_REST_Response) {
            return $files; // validation error
        }

        $attachmentIds = $this->storeAttachments($files);

        $postId = wp_insert_post([
            'post_type'    => 'zgloszenie',
            'post_status'  => 'private',
            'post_title'   => $title,
            'post_content' => $message,
        ], true);

        if (is_wp_error($postId)) {
            return $this->error($isEn ? 'Something went wrong. Please try again.' : 'Coś poszło nie tak. Spróbuj ponownie.', 500);
        }

        update_post_meta($postId, '_wb_status', 'new');
        update_post_meta($postId, '_wb_contact', $contact ?: '');
        update_post_meta($postId, '_wb_lang', $isEn ? 'en' : 'pl');
        update_post_meta($postId, '_wb_attachments', $attachmentIds);
        foreach ($attachmentIds as $id) {
            wp_update_post(['ID' => $id, 'post_parent' => $postId]);
        }

        $this->notifyCommittee($postId, $title, $message, $contact, $attachmentIds, $isEn);

        if ($contact && is_email($contact)) {
            $this->acknowledge($contact, $title, $isEn);
        }

        return new WP_REST_Response(['ok' => true], 200);
    }

    /**
     * Shared spam guard. Honeypot hits get a fake 200 so bots don't learn they
     * were caught; a missing/invalid nonce is rejected.
     */
    private function guard(WP_REST_Request $request): ?WP_REST_Response
    {
        if (trim((string) $request->get_param('website')) !== '') {
            return new WP_REST_Response(['ok' => true], 200);
        }

        $nonce = $request->get_header('X-WP-Nonce');
        if (! $nonce || ! wp_verify_nonce($nonce, 'wp_rest')) {
            return $this->error('Invalid session. Reload the page and try again.', 403);
        }

        return null;
    }

    // ---- Attachments ---------------------------------------------------------

    /**
     * Normalise + validate uploaded files into a list of single-file arrays.
     * Returns a WP_REST_Response on validation failure.
     *
     * @return array<int,array>|WP_REST_Response
     */
    private function collectFiles(WP_REST_Request $request)
    {
        $isEn = $request->get_param('lang') === 'en';
        $raw = $request->get_file_params()['attachments'] ?? null;

        if (! $raw || empty($raw['name'])) {
            return [];
        }

        // Normalise the $_FILES multi-file shape into a flat list.
        $names = (array) $raw['name'];
        $files = [];
        foreach ($names as $i => $name) {
            if (($raw['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || $name === '') {
                continue;
            }
            $files[] = [
                'name'     => $name,
                'type'     => $raw['type'][$i] ?? '',
                'tmp_name' => $raw['tmp_name'][$i] ?? '',
                'error'    => $raw['error'][$i] ?? UPLOAD_ERR_OK,
                'size'     => $raw['size'][$i] ?? 0,
            ];
        }

        if (count($files) > self::MAX_FILES) {
            return $this->error($isEn
                ? 'You can attach up to '.self::MAX_FILES.' files.'
                : 'Możesz dołączyć maksymalnie '.self::MAX_FILES.' plików.');
        }

        foreach ($files as $file) {
            if ($file['size'] > self::MAX_BYTES) {
                return $this->error($isEn ? 'Each file must be under 10 MB.' : 'Każdy plik musi być mniejszy niż 10 MB.');
            }

            $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
            $ext = strtolower((string) ($check['ext'] ?: pathinfo($file['name'], PATHINFO_EXTENSION)));
            if (! isset(self::ALLOWED[$ext])) {
                return $this->error($isEn
                    ? 'Allowed file types: PDF, DOC, DOCX, JPG, PNG.'
                    : 'Dozwolone typy plików: PDF, DOC, DOCX, JPG, PNG.');
            }
        }

        return $files;
    }

    /**
     * Store validated files under uploads/zgloszenia/ with random names, protect
     * the directory, and create attachment posts. Returns the attachment IDs.
     *
     * @param  array<int,array>  $files
     * @return array<int,int>
     */
    private function storeAttachments(array $files): array
    {
        if (! $files) {
            return [];
        }

        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/image.php';

        $toSubdir = fn (array $dirs) => $this->privateUploadDir($dirs);
        $randomName = function (array $file) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            return array_merge($file, ['name' => wp_generate_uuid4().'.'.$ext]);
        };

        add_filter('upload_dir', $toSubdir);
        add_filter('wp_handle_upload_prefilter', $randomName);

        $ids = [];
        foreach ($files as $file) {
            $moved = wp_handle_upload($file, ['test_form' => false]);
            if (! $moved || isset($moved['error'])) {
                continue;
            }

            $id = wp_insert_attachment([
                'post_mime_type' => $moved['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($moved['file'])),
                'post_status'    => 'private',
            ], $moved['file']);

            if (! is_wp_error($id)) {
                wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $moved['file']));
                $ids[] = $id;
            }
        }

        remove_filter('upload_dir', $toSubdir);
        remove_filter('wp_handle_upload_prefilter', $randomName);

        return $ids;
    }

    /** Route uploads to a protected sub-directory (created + hardened on demand). */
    private function privateUploadDir(array $dirs): array
    {
        $subdir = '/'.self::SUBDIR;
        $dirs['path']   = $dirs['basedir'].$subdir;
        $dirs['url']    = $dirs['baseurl'].$subdir;
        $dirs['subdir'] = $subdir;

        if (! is_dir($dirs['path'])) {
            wp_mkdir_p($dirs['path']);
        }

        $htaccess = $dirs['path'].'/.htaccess';
        if (! file_exists($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
            file_put_contents($dirs['path'].'/index.html', '');
        }

        return $dirs;
    }

    /** Stream an attachment to an authorised admin (direct web access is denied). */
    public function downloadAttachment(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if (! current_user_can('edit_posts') || ! wp_verify_nonce($_GET['_wpnonce'] ?? '', 'arpi_wb_file_'.$id)) {
            wp_die('Brak dostępu.', 403);
        }

        $post = get_post($id);
        $parent = $post ? get_post($post->post_parent) : null;
        if (! $post || $post->post_type !== 'attachment' || ! $parent || $parent->post_type !== 'zgloszenie') {
            wp_die('Nie znaleziono pliku.', 404);
        }

        $path = get_attached_file($id);
        if (! $path || ! file_exists($path)) {
            wp_die('Plik niedostępny.', 404);
        }

        nocache_headers();
        header('Content-Type: '.($post->post_mime_type ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="'.basename($path).'"');
        header('Content-Length: '.filesize($path));
        readfile($path);
        exit;
    }

    // ---- Notifications -------------------------------------------------------

    private function notifyCommittee(int $postId, string $title, string $message, string $contact, array $attachmentIds, bool $isEn): void
    {
        $contactLabel = $contact ?: ($isEn ? '(anonymous)' : '(anonimowo)');

        // The message is Trix HTML, already run through wp_kses_post — safe to embed.
        $body =
            '<p>'.($isEn ? 'A new whistleblower report has been submitted.' : 'Wpłynęło nowe zgłoszenie sygnalisty.').'</p>'
            .'<p><strong>'.($isEn ? 'Title' : 'Tytuł').':</strong> '.esc_html($title).'</p>'
            .'<p><strong>'.($isEn ? 'Reporter contact' : 'Kontakt zgłaszającego').':</strong> '.esc_html($contactLabel).'</p>'
            .'<p><strong>'.($isEn ? 'Message' : 'Treść').':</strong></p>'
            .'<div>'.$message.'</div>'
            .'<p><a href="'.esc_url(get_edit_post_link($postId, 'raw')).'">'
            .($isEn ? 'Open in the register' : 'Otwórz w rejestrze').'</a></p>';

        // Attach the files directly for the (authorised) committee recipients.
        $attachments = array_filter(array_map('get_attached_file', $attachmentIds));

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        wp_mail(
            $this->recipients(),
            ($isEn ? 'New whistleblower report: ' : 'Nowe zgłoszenie sygnalisty: ').$title,
            $body,
            $headers,
            $attachments
        );
    }

    private function acknowledge(string $to, string $title, bool $isEn): void
    {
        $subject = $isEn ? 'We have received your report' : 'Potwierdzenie przyjęcia zgłoszenia';
        $body = $isEn
            ? "Thank you — we have received your report \"{$title}\".\n\n"
                ."It has been passed to the persons handling whistleblower reports. In line with the "
                ."applicable rules we will follow up within the required time. This message is an "
                ."automatic confirmation of receipt — please do not reply."
            : "Dziękujemy — otrzymaliśmy Twoje zgłoszenie „{$title}”.\n\n"
                ."Zostało ono przekazane osobom obsługującym zgłoszenia sygnalistów. Zgodnie z "
                ."obowiązującymi zasadami podejmiemy działania następcze w wymaganym terminie. "
                ."Ta wiadomość to automatyczne potwierdzenie odbioru — prosimy na nią nie odpowiadać.";

        wp_mail($to, $subject, $body, ['Content-Type: text/plain; charset=UTF-8']);
    }

    /**
     * Committee recipient(s), per environment (wp_get_environment_type()):
     *  - production: the standing whistleblower committee;
     *  - staging: test inbox + Michał Pudło;
     *  - development/local: test inbox only;
     *  - anything else: safe fallback (test inbox + Michał Pudło).
     */
    private function recipients(): array
    {
        $list = match (wp_get_environment_type()) {
            'production' => [
                'committee-5@example.test',
                'committee-6@example.test',
                'committee-3@example.test',
                'committee-4@example.test',
                'committee-7@example.test',
                'committee-2@example.test',
            ],
            'staging'              => ['91374040+WoXuS@users.noreply.github.com', 'committee-1@example.test'],
            'development', 'local' => ['91374040+WoXuS@users.noreply.github.com'],
            default                => ['91374040+WoXuS@users.noreply.github.com', 'committee-1@example.test'],
        };

        return array_values(array_filter($list, 'is_email'));
    }

    // ---- Admin register: triage meta box, columns ---------------------------

    public function addMetaBox(): void
    {
        add_meta_box('arpi_wb_details', 'Szczegóły zgłoszenia', [$this, 'renderMetaBox'], 'zgloszenie', 'side', 'high');
    }

    public function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('arpi_wb_status_save', 'arpi_wb_status_nonce');

        $status = get_post_meta($post->ID, '_wb_status', true) ?: 'new';
        $contact = get_post_meta($post->ID, '_wb_contact', true);
        $lang = get_post_meta($post->ID, '_wb_lang', true) ?: 'pl';
        $attachmentIds = (array) get_post_meta($post->ID, '_wb_attachments', true);

        $statuses = ['new' => 'Nowe', 'in_progress' => 'W toku', 'closed' => 'Zamknięte'];

        echo '<p><strong>Status</strong><br><select name="arpi_wb_status" style="width:100%">';
        foreach ($statuses as $value => $label) {
            printf('<option value="%s"%s>%s</option>', esc_attr($value), selected($status, $value, false), esc_html($label));
        }
        echo '</select></p>';

        echo '<p><strong>Data wpływu:</strong><br>'.esc_html(get_the_date('Y-m-d H:i', $post)).'</p>';
        echo '<p><strong>Język:</strong> '.esc_html(strtoupper($lang)).'</p>';
        echo '<p><strong>Kontakt zgłaszającego:</strong><br>'
            .($contact ? '<a href="mailto:'.esc_attr($contact).'">'.esc_html($contact).'</a>' : '<em>anonimowo</em>').'</p>';

        echo '<p><strong>Załączniki:</strong><br>';
        if ($attachmentIds) {
            foreach ($attachmentIds as $id) {
                $url = wp_nonce_url(admin_url('admin-post.php?action=arpi_wb_file&id='.$id), 'arpi_wb_file_'.$id);
                echo '<a href="'.esc_url($url).'">'.esc_html(get_the_title($id) ?: ('#'.$id)).'</a><br>';
            }
        } else {
            echo '<em>brak</em>';
        }
        echo '</p>';
    }

    public function saveStatus(int $postId): void
    {
        if (! isset($_POST['arpi_wb_status_nonce']) || ! wp_verify_nonce($_POST['arpi_wb_status_nonce'], 'arpi_wb_status_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || ! current_user_can('edit_post', $postId)) {
            return;
        }
        if (isset($_POST['arpi_wb_status'])) {
            update_post_meta($postId, '_wb_status', sanitize_key($_POST['arpi_wb_status']));
        }
    }

    public function columns(array $columns): array
    {
        $out = ['cb' => $columns['cb'] ?? '', 'title' => $columns['title'] ?? 'Tytuł'];
        $out['wb_status'] = 'Status';
        $out['wb_files'] = 'Załączniki';
        $out['date'] = $columns['date'] ?? 'Data';

        return $out;
    }

    public function renderColumn(string $column, int $postId): void
    {
        if ($column === 'wb_status') {
            $map = ['new' => 'Nowe', 'in_progress' => 'W toku', 'closed' => 'Zamknięte'];
            $status = get_post_meta($postId, '_wb_status', true) ?: 'new';
            echo esc_html($map[$status] ?? $status);
        }

        if ($column === 'wb_files') {
            echo (int) count((array) get_post_meta($postId, '_wb_attachments', true));
        }
    }

    // ---- Helpers -------------------------------------------------------------

    private function truthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    private function error(string $message, int $status = 422): WP_REST_Response
    {
        return new WP_REST_Response(['ok' => false, 'message' => $message], $status);
    }
}
