<?php

namespace App\Support;

use MailPoet\API\API as MailPoetAPI;

/**
 * Thin wrapper over MailPoet's public PHP API (MP v1).
 *
 * Degrades gracefully when the plugin is inactive (returns false / null) so the
 * theme never fatals on an environment without MailPoet (CLI, fresh staging).
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

    /**
     * Resolve a list (segment) id by name, creating it if absent.
     */
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
     * Upsert a subscriber and (re)subscribe them to the given lists (by name).
     *
     * MailPoet's API ignores an explicit `status`; the resulting status is driven
     * by the signup-confirmation setting. Pass $confirm = true for genuine
     * newsletter opt-ins (double opt-in — MailPoet sends the confirmation email in
     * prod); pass false for plain contact leads (they land in the CRM list without
     * an email and are never mailed until confirmed).
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
