<?php

namespace App\Providers;

use App\Support\Mailpoet;
use Illuminate\Support\ServiceProvider;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST endpoints backing the front-end forms (contact + footer newsletter),
 * guarded by the REST nonce + a honeypot. Leads go into MailPoet lists; the
 * contact form also emails the office. MailPoet calls degrade to no-ops when the
 * plugin is inactive, so submissions never fatal.
 */
class ContactServiceProvider extends ServiceProvider
{
    private const OFFICE_EMAIL = 'contact@arpiaccounting.com';

    /** Non-production envs (dev + staging) route notifications to a test inbox. */
    private const OFFICE_EMAIL_NONPROD = 'dev@example.test';

    public function boot(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('arpi/v1', '/contact', [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleContact'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('arpi/v1', '/newsletter', [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleNewsletter'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('arpi/v1', '/dbip-access', [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleDbipAccess'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function handleContact(WP_REST_Request $request): WP_REST_Response
    {
        if ($drop = $this->guard($request)) {
            return $drop;
        }

        $name    = sanitize_text_field((string) $request->get_param('name'));
        $email   = sanitize_email((string) $request->get_param('email'));
        $phone   = sanitize_text_field((string) $request->get_param('phone'));
        $company = sanitize_text_field((string) $request->get_param('company'));
        $topic   = sanitize_text_field((string) $request->get_param('topic'));
        $message = sanitize_textarea_field((string) $request->get_param('message'));
        $consent = $this->truthy($request->get_param('consent'));
        $wantsNewsletter = $this->truthy($request->get_param('newsletter'));
        $isEn = $request->get_param('lang') === 'en';

        if ($name === '' || ! is_email($email) || $message === '' || ! $consent) {
            return $this->error($isEn ? 'Please fill in the required fields.' : 'Uzupełnij wymagane pola.');
        }

        [$first, $last] = $this->splitName($name);

        // Best-effort — does not block lead capture.
        $this->notifyOffice(compact('name', 'email', 'phone', 'company', 'topic', 'message'), $isEn);

        // Double opt-in only when they asked for the newsletter; a plain enquiry
        // just lands in the Kontakt CRM list.
        $lists = [$isEn ? 'Kontakt EN' : 'Kontakt PL'];
        if ($wantsNewsletter) {
            $lists[] = $isEn ? 'Newsletter EN' : 'Newsletter PL';
        }
        Mailpoet::subscribe($email, ['first_name' => $first, 'last_name' => $last], $lists, $wantsNewsletter);

        return new WP_REST_Response(['ok' => true], 200);
    }

    public function handleNewsletter(WP_REST_Request $request): WP_REST_Response
    {
        if ($drop = $this->guard($request)) {
            return $drop;
        }

        $email = sanitize_email((string) $request->get_param('email'));
        $isEn = $request->get_param('lang') === 'en';

        if (! is_email($email)) {
            return $this->error($isEn ? 'Enter a valid email address.' : 'Podaj poprawny adres e-mail.');
        }

        Mailpoet::subscribe($email, [], [$isEn ? 'Newsletter EN' : 'Newsletter PL'], true);

        return new WP_REST_Response(['ok' => true], 200);
    }

    /**
     * DBiP "receive a password" form (English-only publication). Mirrors the legacy
     * MailPoet form: adds the requester to the DBiP-access lists and emails them the
     * shared chapter password so they can unlock the content.
     */
    public function handleDbipAccess(WP_REST_Request $request): WP_REST_Response
    {
        if ($drop = $this->guard($request)) {
            return $drop;
        }

        $first  = sanitize_text_field((string) $request->get_param('first_name'));
        $last   = sanitize_text_field((string) $request->get_param('last_name'));
        $email  = sanitize_email((string) $request->get_param('email'));
        $postId = (int) $request->get_param('post');

        if ($first === '' || $last === '' || ! is_email($email)) {
            return $this->error('Please fill in all the fields with a valid email address.');
        }

        $password = $this->dbipPassword($postId);
        if ($password === '') {
            return $this->error('Access is temporarily unavailable. Please try again later.', 500);
        }

        // CRM: add to the DBiP-access lists (matches the legacy MailPoet form 7).
        Mailpoet::subscribe($email, ['first_name' => $first, 'last_name' => $last], [
            'Dostęp do DBiP',
            'Pomocnicza: Dostęp do DBiP',
        ], false);

        $this->sendDbipPassword($email, $first, $password, $postId);

        return new WP_REST_Response([
            'ok'      => true,
            'message' => 'Your request has been processed. A message with your password has been sent to the email address you provided. If you do not see it, please check your spam folder.',
        ], 200);
    }

    /**
     * Every protected DBiP chapter shares one password. Read it from the requested
     * chapter, falling back to any protected chapter.
     */
    private function dbipPassword(int $postId): string
    {
        $post = $postId ? get_post($postId) : null;
        if ($post && $post->post_type === 'dbip-chapters' && $post->post_password !== '') {
            return $post->post_password;
        }

        $ids = get_posts([
            'post_type'    => 'dbip-chapters',
            'numberposts'  => 1,
            'has_password' => true,
            'fields'       => 'ids',
        ]);

        return $ids ? (string) get_post($ids[0])->post_password : '';
    }

    private function sendDbipPassword(string $email, string $first, string $password, int $postId): void
    {
        $url = $postId ? get_permalink($postId) : get_post_type_archive_link('dbip-chapters');

        $lines = [
            'Hello '.($first !== '' ? $first : 'there').',',
            '',
            'Thank you for your interest in our "Doing Business in Poland" publication.',
            '',
            'Your access password is: '.$password,
            '',
            'Enter it on the publication page to unlock the content:',
            $url,
            '',
            'Kind regards,',
            'ARPI Accounting',
        ];

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        wp_mail($email, 'Your access to Doing Business in Poland', implode("\n", $lines), $headers);
    }

    // Honeypot hits get a fake 200 so bots don't learn they were caught.
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

    private function notifyOffice(array $f, bool $isEn): void
    {
        $subject = ($isEn ? 'New enquiry from the website: ' : 'Nowe zapytanie ze strony: ') . ($f['topic'] ?: '—');

        $lines = [
            ($isEn ? 'Name: ' : 'Imię i nazwisko: ') . $f['name'],
            'E-mail: ' . $f['email'],
            ($isEn ? 'Phone: ' : 'Telefon: ') . ($f['phone'] ?: '—'),
            ($isEn ? 'Company: ' : 'Firma: ') . ($f['company'] ?: '—'),
            ($isEn ? 'Topic: ' : 'Temat: ') . ($f['topic'] ?: '—'),
            '',
            ($isEn ? 'Message:' : 'Wiadomość:'),
            $f['message'],
        ];

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $f['name'] . ' <' . $f['email'] . '>',
        ];

        wp_mail($this->officeEmail(), $subject, implode("\n", $lines), $headers);
    }

    private function officeEmail(): string
    {
        return wp_get_environment_type() === 'production'
            ? self::OFFICE_EMAIL
            : self::OFFICE_EMAIL_NONPROD;
    }

    /**
     * @return array{0:string,1:string} [first name, last name]
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function truthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    private function error(string $message, int $status = 422): WP_REST_Response
    {
        return new WP_REST_Response(['ok' => false, 'message' => $message], $status);
    }
}
