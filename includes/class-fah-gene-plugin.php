<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main bootstrap for FAH Genealogy functionality.
 */
class FAH_Gene_Plugin {

    /**
     * Initialise hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_person_cpt' ) );
    }

    /**
     * Register the Person custom post type.
     *
     * This is the basic entity representing one individual.
     */
    public static function register_person_cpt() {
        $labels = array(
            'name'               => __( 'People', 'fah-genealogy' ),
            'singular_name'      => __( 'Person', 'fah-genealogy' ),
            'add_new'            => __( 'Add New', 'fah-genealogy' ),
            'add_new_item'       => __( 'Add New Person', 'fah-genealogy' ),
            'edit_item'          => __( 'Edit Person', 'fah-genealogy' ),
            'new_item'           => __( 'New Person', 'fah-genealogy' ),
            'view_item'          => __( 'View Person', 'fah-genealogy' ),
            'search_items'       => __( 'Search People', 'fah-genealogy' ),
            'not_found'          => __( 'No people found', 'fah-genealogy' ),
            'not_found_in_trash' => __( 'No people found in Trash', 'fah-genealogy' ),
            'menu_name'          => __( 'Family Archive Hub', 'fah-genealogy' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-networking',
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'has_archive'        => true,
            'rewrite'            => array( 'slug' => 'people' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'gene_person', $args );
    }
}
