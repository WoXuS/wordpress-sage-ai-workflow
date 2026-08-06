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
