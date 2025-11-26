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

        // Admin-only hooks.
        if ( is_admin() ) {
            add_action( 'add_meta_boxes', array( __CLASS__, 'register_person_meta_boxes' ) );
            add_action( 'save_post_gene_person', array( __CLASS__, 'save_person_meta' ), 10, 2 );
            add_filter( 'enter_title_here', array( __CLASS__, 'person_title_placeholder' ), 10, 2 );
        }
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

    /**
     * Change the title placeholder text for Person posts.
     */
    public static function person_title_placeholder( $title, $post ) {
        if ( $post->post_type === 'gene_person' ) {
            return __( 'Full name (e.g. John Michael Smith))', 'fah-genealogy' );
        }
        return $title;
    }

    /**
     * Register meta boxes for Person details.
     */
    public static function register_person_meta_boxes() {
        add_meta_box(
            'fah_person_core_details',
            __( 'Person Details', 'fah-genealogy' ),
            array( __CLASS__, 'render_person_meta_box' ),
            'gene_person',
            'normal',
            'high'
        );
    }

    /**
     * Render the Person Details meta box.
     */
    public static function render_person_meta_box( $post ) {
        // Security nonce.
        wp_nonce_field( 'fah_save_person_meta', 'fah_person_meta_nonce' );

        $given_names   = get_post_meta( $post->ID, '_fah_given_names', true );
        $surname       = get_post_meta( $post->ID, '_fah_surname', true );
        $gender        = get_post_meta( $post->ID, '_fah_gender', true );
        $birth_date    = get_post_meta( $post->ID, '_fah_birth_date', true );
        $birth_place   = get_post_meta( $post->ID, '_fah_birth_place', true );
        $death_date    = get_post_meta( $post->ID, '_fah_death_date', true );
        $death_place   = get_post_meta( $post->ID, '_fah_death_place', true );
        $is_living     = get_post_meta( $post->ID, '_fah_is_living', true );

        ?>
        <p>
            <em><?php esc_html_e( 'Use the fields below for structured data. The title field above should be the full display name.', 'fah-genealogy' ); ?></em>
        </p>

        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="fah_given_names"><?php esc_html_e( 'Given names', 'fah-genealogy' ); ?></label></th>
                <td>
                    <input type="text" id="fah_given_names" name="fah_given_names" class="regular-text"
                           value="<?php echo esc_attr( $given_names ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="fah_surname"><?php esc_html_e( 'Surname', 'fah-genealogy' ); ?></label></th>
                <td>
                    <input type="text" id="fah_surname" name="fah_surname" class="regular-text"
                           value="<?php echo esc_attr( $surname ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Gender', 'fah-genealogy' ); ?></th>
                <td>
                    <select name="fah_gender" id="fah_gender">
                        <option value=""><?php esc_html_e( '— Select —', 'fah-genealogy' ); ?></option>
                        <option value="male" <?php selected( $gender, 'male' ); ?>><?php esc_html_e( 'Male', 'fah-genealogy' ); ?></option>
                        <option value="female" <?php selected( $gender, 'female' ); ?>><?php esc_html_e( 'Female', 'fah-genealogy' ); ?></option>
                        <option value="other" <?php selected( $gender, 'other' ); ?>><?php esc_html_e( 'Other / Unknown', 'fah-genealogy' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="fah_birth_date"><?php esc_html_e( 'Date of birth', 'fah-genealogy' ); ?></label></th>
                <td>
                    <input type="text" id="fah_birth_date" name="fah_birth_date" class="regular-text"
                           placeholder="<?php esc_attr_e( 'e.g. 12 Mar 1950 or Abt 1950', 'fah-genealogy' ); ?>"
                           value="<?php echo esc_attr( $birth_date ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="fah_birth_place"><?php esc_html_e( 'Place of birth', 'fah-genealogy' ); ?></label></th>
                <td>
                    <input type="text" id="fah_birth_place" name="fah_birth_place" class="regular-text"
                           value="<?php echo esc_attr( $birth_place ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="fah_death_date"><?php esc_html_e( 'Date of death', 'fah-genealogy' ); ?></label></th>
                <td>
                    <input type="text" id="fah_death_date" name="fah_death_date" class="regular-text"
                           placeholder="<?php esc_attr_e( 'Leave blank if living or unknown', 'fah-genealogy' ); ?>"
                           value="<?php echo esc_attr( $death_date ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="fah_death_place"><?php esc_html_e( 'Place of death', 'fah-genealogy' ); ?></label></th>
                <td>
                    <input type="text" id="fah_death_place" name="fah_death_place" class="regular-text"
                           value="<?php echo esc_attr( $death_place ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Living?', 'fah-genealogy' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="fah_is_living" value="1" <?php checked( $is_living, '1' ); ?> />
                        <?php esc_html_e( 'Tick if this person is believed to be living.', 'fah-genealogy' ); ?>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Save Person meta from the meta box.
     */
    public static function save_person_meta( $post_id, $post ) {
        // Check nonce.
        if ( ! isset( $_POST['fah_person_meta_nonce'] ) || ! wp_verify_nonce( $_POST['fah_person_meta_nonce'], 'fah_save_person_meta' ) ) {
            return;
        }

        // Autosave? bail.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Revisions? bail.
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Check user capability.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Only for our CPT.
        if ( $post->post_type !== 'gene_person' ) {
            return;
        }

        $given_names   = isset( $_POST['fah_given_names'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_given_names'] ) ) : '';
        $surname       = isset( $_POST['fah_surname'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_surname'] ) ) : '';
        $gender        = isset( $_POST['fah_gender'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_gender'] ) ) : '';
        $birth_date    = isset( $_POST['fah_birth_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_birth_date'] ) ) : '';
        $birth_place   = isset( $_POST['fah_birth_place'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_birth_place'] ) ) : '';
        $death_date    = isset( $_POST['fah_death_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_death_date'] ) ) : '';
        $death_place   = isset( $_POST['fah_death_place'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_death_place'] ) ) : '';
        $is_living     = isset( $_POST['fah_is_living'] ) ? '1' : '0';

        $fields = array(
            '_fah_given_names' => $given_names,
            '_fah_surname'     => $surname,
            '_fah_gender'      => $gender,
            '_fah_birth_date'  => $birth_date,
            '_fah_birth_place' => $birth_place,
            '_fah_death_date'  => $death_date,
            '_fah_death_place' => $death_place,
            '_fah_is_living'   => $is_living,
        );

        foreach ( $fields as $meta_key => $value ) {
            update_post_meta( $post_id, $meta_key, $value );
        }

        // Auto-set the post title from Given names + Surname.
        $full_name = trim( $given_names . ' ' . $surname );

        if ( ! empty( $full_name ) ) {
            // Only update if title is empty or different, to avoid infinite loops.
            if ( $post->post_title !== $full_name ) {
                remove_action( 'save_post_gene_person', array( __CLASS__, 'save_person_meta' ), 10 );
                wp_update_post(
                    array(
                        'ID'         => $post_id,
                        'post_title' => $full_name,
                        // Optional: update slug too, based on full name.
                        'post_name'  => sanitize_title( $full_name ),
                    )
                );
                add_action( 'save_post_gene_person', array( __CLASS__, 'save_person_meta' ), 10, 2 );
            }
        }
    }
