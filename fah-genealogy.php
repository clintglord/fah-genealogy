<?php
/**
 * Plugin Name: Family Archive Hub – Genealogy Engine
 * Plugin URI: https://familyarchivehub.com/
 * Description: Core genealogy engine for Family Archive Hub – people, events, relationships, and future GEDCOM import.
 * Version: 0.1.0
 * Author: Clinton / FAH
 * Text Domain: fah-genealogy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Basic constants
 */
define( 'FAH_GENEALOGY_VERSION', '0.1.0' );
define( 'FAH_GENEALOGY_DB_VERSION', '1' );
define( 'FAH_GENEALOGY_PLUGIN_FILE', __FILE__ );
define( 'FAH_GENEALOGY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAH_GENEALOGY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Includes
 */
require_once FAH_GENEALOGY_PLUGIN_DIR . 'includes/class-fah-gene-db.php';
require_once FAH_GENEALOGY_PLUGIN_DIR . 'includes/class-fah-gene-plugin.php';

/**
 * Activation hook – create/upgrade DB tables and flush rewrites.
 */
function fah_genealogy_activate() {
    // Create/upgrade custom tables.
    FAH_Gene_DB::activate();

    // Register CPT so its rewrite rules exist during activation.
    FAH_Gene_Plugin::register_person_cpt();

    // Now flush rewrite rules so /people/... URLs work immediately.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'fah_genealogy_activate' );

/**
 * Deactivation hook – flush rewrites to clean up.
 */
function fah_genealogy_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'fah_genealogy_deactivate' );

/**
 * On plugins_loaded, check for DB upgrades and boot plugin.
 */
add_action(
    'plugins_loaded',
    function () {
        FAH_Gene_DB::maybe_upgrade();
        FAH_Gene_Plugin::init();
    }
);
