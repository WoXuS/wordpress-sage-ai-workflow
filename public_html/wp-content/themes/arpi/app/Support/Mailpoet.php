<?php

namespace App\Support;

use MailPoet\API\API as MailPoetAPI;

/**
 * Wrapper over MailPoet's public PHP API (MP v1). Degrades to false/null when the
 * plugin is inactive so the theme never fatals without MailPoet (CLI, fresh staging).
 */
class Mailpoet
{
    public static function available(): bool
    {
        return class_exists(MailPoetAPI::class);
    }

    protected static function api()
    {
        return MailPoetAPI::MP('v1');
    }

    public static function listId(string $name, string $description = ''): ?int
    {
        if (! self::available()) {
            return null;
        }

        $api = self::api();

        foreach ($api->getLists() as $list) {
            if (strcasecmp($list['name'], $name) === 0) {
                return (int) $list['id'];
            }
        }

        try {
            $list = $api->addList(['name' => $name, 'description' => $description]);

            return (int) $list['id'];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * MailPoet ignores an explicit `status`; status comes from the signup-confirmation
     * setting. $confirm = true → double opt-in (sends confirmation email in prod);
     * false → plain lead, lands in the list unconfirmed and is never mailed.
     *
     * @param  array<string,string>  $data   first_name / last_name
     * @param  string[]              $listNames
     */
    public static function subscribe(string $email, array $data, array $listNames, bool $confirm = false): bool
    {
        if (! self::available() || ! is_email($email)) {
            return false;
        }

        $api = self::api();

        $listIds = array_values(array_filter(array_map(
            fn ($name) => self::listId($name),
            $listNames
        )));

        if (! $listIds) {
            return false;
        }

        $options = [
            'send_confirmation_email'      => $confirm,
            'schedule_welcome_email'       => false,
            'skip_subscriber_notification' => true,
        ];

        $subscriber = array_filter([
            'email'      => $email,
            'first_name' => $data['first_name'] ?? '',
            'last_name'  => $data['last_name'] ?? '',
        ], fn ($v) => $v !== '');

        try {
            $api->addSubscriber($subscriber, $listIds, $options);

            return true;
        } catch (\Throwable $e) {
            // Subscriber already exists → just add the list memberships.
            try {
                $existing = $api->getSubscriber($email);
                $api->subscribeToLists($existing['id'], $listIds, $options);

                return true;
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }
}
