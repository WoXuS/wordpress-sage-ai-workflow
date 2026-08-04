<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

function arpi_env($key, $default = null) {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($v === false || $v === null) ? $default : $v;
}

define('DB_NAME', arpi_env('DB_NAME'));
define('DB_USER', arpi_env('DB_USER'));
define('DB_PASSWORD', arpi_env('DB_PASSWORD'));
define('DB_HOST', arpi_env('DB_HOST', 'db'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

foreach (['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $k) {
    define($k, arpi_env($k, 'change-me'));
}

$table_prefix = arpi_env('DB_PREFIX', 'wp_');

define('WP_HOME', arpi_env('WP_HOME', 'http://localhost:8080'));
define('WP_SITEURL', WP_HOME);

define('WP_DEBUG', filter_var(arpi_env('WP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_LOG', WP_DEBUG);
define('WP_DEBUG_DISPLAY', false);
define('WP_ENVIRONMENT_TYPE', arpi_env('WP_ENV', 'production'));
define('DISALLOW_FILE_EDIT', true);

// Write plugin/theme/core updates directly to disk. WP's auto-detection falls
// back to an FTP prompt when the runtime user (php-fpm: www-data) differs from
// the owner of core files — true both in this container and on per-user PHP
// hosting like cyberFolks. Overridable via .env if a host ever needs otherwise.
define('FS_METHOD', arpi_env('FS_METHOD', 'direct'));

// FluentSMTP reads this constant for the Brevo (Sendinblue) connection when the
// connection is set to store its key in wp-config. The value lives in .env
// (untracked, above the docroot) — never inline the key here.
if ($brevoSmtpKey = arpi_env('BREVO_SMTP_KEY')) {
    define('FLUENTMAIL_SENDINBLUE_API_KEY', $brevoSmtpKey);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
