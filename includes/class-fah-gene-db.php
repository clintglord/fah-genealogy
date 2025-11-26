<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles database tables for FAH Genealogy.
 */
class FAH_Gene_DB {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        update_option( 'fah_genealogy_db_version', FAH_GENEALOGY_DB_VERSION );
    }

    /**
     * Run on plugins_loaded to see if DB needs upgrading.
     */
    public static function maybe_upgrade() {
        $installed_version = get_option( 'fah_genealogy_db_version' );

        if ( $installed_version !== FAH_GENEALOGY_DB_VERSION ) {
            self::create_tables();
            update_option( 'fah_genealogy_db_version', FAH_GENEALOGY_DB_VERSION );
        }
    }

    /**
     * Create or update custom tables.
     */
    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $events_table        = $wpdb->prefix . 'fah_events';
        $relationships_table = $wpdb->prefix . 'fah_relationships';
        $sources_table       = $wpdb->prefix . 'fah_sources';

        $sql_events = "CREATE TABLE {$events_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            person_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            date_text VARCHAR(50) NULL,
            date_sort INT NULL,
            place VARCHAR(255) NULL,
            description TEXT NULL,
            source_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY person_idx (person_id),
            KEY type_idx (event_type),
            KEY date_idx (date_sort)
        ) {$charset_collate};";

        $sql_relationships = "CREATE TABLE {$relationships_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            person_id BIGINT UNSIGNED NOT NULL,
            related_person_id BIGINT UNSIGNED NOT NULL,
            relationship_type ENUM('father','mother','child','spouse','partner','sibling','other') NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY person_idx (person_id),
            KEY related_idx (related_person_id),
            KEY type_idx (relationship_type)
        ) {$charset_collate};";

        $sql_sources = "CREATE TABLE {$sources_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            citation TEXT NULL,
            url VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        dbDelta( $sql_events );
        dbDelta( $sql_relationships );
        dbDelta( $sql_sources );
    }
}
