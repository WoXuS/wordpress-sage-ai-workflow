<?php

/**
 * Import portable MailPoet settings from the legacy prod install into the current
 * MailPoet install (dev).
 *
 *   make wp ARGS="eval-file /var/app/scripts/import-mailpoet-settings.php"
 *
 * Idempotent — re-running just re-applies the same values.
 *
 * Only install-PORTABLE settings are migrated. Deliberately SKIPPED:
 *   - mta / mta_group / smtp_provider  → prod's MailPoet Sending Service API key
 *     (a secret, install-specific). The client configures sending (Brevo) in wp-admin.
 *   - subscription (pages)             → prod page IDs (877/897/4401) don't exist here.
 *   - db_version / version             → prod (5.17.2 / 3.35.3) is OLDER than dev; never overwrite.
 *   - public_id / new_public_id / analytics / premium / installed_at / homepage / cron_* → per-install.
 */

if (! defined('ABSPATH')) {
    exit;
}

// Curated, portable settings copied 1:1 from reference/prod-db.sql.
$settings = [
    'sender' => [
        'address' => 'contact@arpiaccounting.com',
        'name'    => 'ARPI Accounting',
    ],
    'reply_to' => [
        'address' => 'contact@arpiaccounting.com',
        'name'    => 'ARPI Accounting',
    ],
    'bounce' => [
        'address' => 'committee-1@example.test',
    ],
    'tracking' => [
        'level' => 'partial',
    ],
    'subscribe' => [
        'on_register' => ['label' => 'Yes, add me to your mailing list', 'enabled' => '0'],
        'on_comment'  => ['label' => 'Yes, add me to your mailing list', 'enabled' => '0'],
    ],
    'signup_confirmation' => [
        // Prod pointed transactional_email_id at newsletter 159 (not migrated) with
        // use_mailpoet_editor=1 → neutralised to 0/0 so MailPoet uses the plain
        // subject/body below instead of a missing editor email.
        'transactional_email_id' => 0,
        'use_mailpoet_editor'    => '0',
        'body'    => "Hello,\n\nWelcome to our newsletter!\n\nPlease confirm your subscription by clicking [activation_link]here[/activation_link]\n\nThank you,\nARPI Accounting",
        'subject' => 'Confirm your subscription to ARPI Accounting blog',
        'enabled' => '1',
    ],
];

$controller = class_exists(\MailPoet\Settings\SettingsController::class)
    ? \MailPoet\Settings\SettingsController::getInstance()
    : null;

global $wpdb;
$table = $wpdb->prefix.'mailpoet_settings';
$now = current_time('mysql');

foreach ($settings as $name => $value) {
    if ($controller) {
        $controller->set($name, $value);
        WP_CLI::log("set (controller): {$name}");

        continue;
    }

    $serialized = serialize($value);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE name = %s", $name));

    if ($exists) {
        $wpdb->update($table, ['value' => $serialized, 'updated_at' => $now], ['name' => $name]);
    } else {
        $wpdb->insert($table, ['name' => $name, 'value' => $serialized, 'created_at' => $now, 'updated_at' => $now]);
    }

    WP_CLI::log("set (db): {$name}");
}

WP_CLI::success('MailPoet portable settings imported.');
