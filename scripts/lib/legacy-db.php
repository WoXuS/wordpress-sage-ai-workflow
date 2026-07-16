<?php
/**
 * Shared connection to the legacy production database.
 *
 * The legacy DB runs standalone via docker-compose.legacy.yml (project
 * `arpi-legacy`, db `arpi_legacy`, root/root) and publishes port 3309 on the
 * host. From inside the php container it is reachable at host.docker.internal.
 *
 * Bring it up before running any importer:
 *   docker compose -f docker-compose.legacy.yml up -d
 *   docker compose -f docker-compose.legacy.yml exec -T legacy-db \
 *     sh -c 'exec mariadb -uroot -proot arpi_legacy' < reference/prod-db.sql
 */

if (! function_exists('arpi_legacy_connect')) {
    /**
     * @return mysqli
     */
    function arpi_legacy_connect(): mysqli
    {
        $host = getenv('LEGACY_DB_HOST') ?: 'host.docker.internal';
        $port = (int) (getenv('LEGACY_DB_PORT') ?: 3309);
        $user = getenv('LEGACY_DB_USER') ?: 'root';
        $pass = getenv('LEGACY_DB_PASS') ?: 'root';
        $name = getenv('LEGACY_DB_NAME') ?: 'arpi_legacy';

        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @mysqli_connect($host, $user, $pass, $name, $port);
        if (! $conn) {
            $msg = "Cannot reach legacy DB at {$host}:{$port} ({$name}): " . mysqli_connect_error()
                . "\nStart it with: docker compose -f docker-compose.legacy.yml up -d"
                . " and import reference/prod-db.sql.";
            if (class_exists('WP_CLI')) {
                WP_CLI::error($msg);
            }
            throw new RuntimeException($msg);
        }
        mysqli_set_charset($conn, 'utf8mb4');

        return $conn;
    }
}
