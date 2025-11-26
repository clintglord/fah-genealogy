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

        if ( is_admin() ) {
            add_action( 'add_meta_boxes', array( __CLASS__, 'register_person_meta_boxes' ) );
            add_action( 'save_post_gene_person', array( __CLASS__, 'save_person_meta' ), 10, 2 );
            add_filter( 'enter_title_here', array( __CLASS__, 'person_title_placeholder' ), 10, 2 );
            add_filter( 'wp_insert_post_data', array( __CLASS__, 'auto_title_on_insert' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        }
    }

    /**
     * Register the Person custom post type.
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
        if ( $post instanceof WP_Post && $post->post_type === 'gene_person' ) {
            return __( 'Full name (e.g. John Michael Smith)', 'fah-genealogy' );
        }

        return $title;
    }

    /**
     * Auto-set title at insert time (server-side safety net).
     */
    public static function auto_title_on_insert( $data, $postarr ) {
        if ( $data['post_type'] !== 'gene_person' ) {
            return $data;
        }

        if ( ! empty( $data['post_title'] ) ) {
            return $data;
        }

        $given_names = isset( $_POST['fah_given_names'] )
            ? sanitize_text_field( wp_unslash( $_POST['fah_given_names'] ) )
            : '';
        $surname     = isset( $_POST['fah_surname'] )
            ? sanitize_text_field( wp_unslash( $_POST['fah_surname'] ) )
            : '';

        $full_name = trim( $given_names . ' ' . $surname );

        if ( ! empty( $full_name ) ) {
            $data['post_title'] = $full_name;
            $data['post_name']  = sanitize_title( $full_name );
        }

        return $data;
    }

    /**
     * Enqueue admin assets (JS to sync title from meta fields).
     */
    public static function enqueue_admin_assets() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'gene_person' ) {
            return;
        }

        wp_enqueue_script(
            'fah-gene-person-title-sync',
            FAH_GENEALOGY_PLUGIN_URL . 'assets/js/fah-person-title-sync.js',
            array( 'wp-data', 'wp-edit-post' ),
            FAH_GENEALOGY_VERSION,
            true
        );
    }

    /**
     * Register meta boxes for Person details, Events, and Relationships.
     */
    public static function register_person_meta_boxes() {
        add_meta_box(
            'fah_person_core_details',
            __( 'Person Details', 'fah-genealogy' ),
            array( __CLASS__, 'render_person_details_meta_box' ),
            'gene_person',
            'normal',
            'high'
        );

        add_meta_box(
            'fah_person_events',
            __( 'Events / Facts', 'fah-genealogy' ),
            array( __CLASS__, 'render_person_events_meta_box' ),
            'gene_person',
            'normal',
            'default'
        );

        add_meta_box(
            'fah_person_relationships',
            __( 'Family Links', 'fah-genealogy' ),
            array( __CLASS__, 'render_person_relationships_meta_box' ),
            'gene_person',
            'side',
            'default'
        );
    }

    /**
     * Render the Person Details meta box.
     */
    public static function render_person_details_meta_box( $post ) {
        wp_nonce_field( 'fah_save_person_meta', 'fah_person_meta_nonce' );

        $given_names = get_post_meta( $post->ID, '_fah_given_names', true );
        $surname     = get_post_meta( $post->ID, '_fah_surname', true );
        $gender      = get_post_meta( $post->ID, '_fah_gender', true );
        $birth_date  = get_post_meta( $post->ID, '_fah_birth_date', true );
        $birth_place = get_post_meta( $post->ID, '_fah_birth_place', true );
        $death_date  = get_post_meta( $post->ID, '_fah_death_date', true );
        $death_place = get_post_meta( $post->ID, '_fah_death_place', true );
        $is_living   = get_post_meta( $post->ID, '_fah_is_living', true );
        ?>
        <p>
            <em><?php esc_html_e( 'Use the fields below for structured data. The title will automatically use the full name (Given names + Surname).', 'fah-genealogy' ); ?></em>
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
     * Render Events / Facts meta box.
     */
    public static function render_person_events_meta_box( $post ) {
        $events      = FAH_Gene_Events::get_events_for_person( $post->ID );
        $event_types = FAH_Gene_Events::get_event_types();

        ?>
        <p><em><?php esc_html_e( 'Add life events and facts for this person. These will later drive timelines and reports.', 'fah-genealogy' ); ?></em></p>

        <table class="widefat striped" id="fah-events-table">
            <thead>
            <tr>
                <th><?php esc_html_e( 'Type', 'fah-genealogy' ); ?></th>
                <th><?php esc_html_e( 'Date (text)', 'fah-genealogy' ); ?></th>
                <th><?php esc_html_e( 'Place', 'fah-genealogy' ); ?></th>
                <th><?php esc_html_e( 'Description / Notes', 'fah-genealogy' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ( ! empty( $events ) ) :
                foreach ( $events as $index => $event ) :
                    ?>
                    <tr>
                        <td>
                            <select name="fah_events[<?php echo esc_attr( $index ); ?>][event_type]">
                                <?php foreach ( $event_types as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $event['event_type'], $key ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text"
                                   name="fah_events[<?php echo esc_attr( $index ); ?>][date_text]"
                                   value="<?php echo esc_attr( $event['date_text'] ); ?>"
                                   class="regular-text" />
                        </td>
                        <td>
                            <input type="text"
                                   name="fah_events[<?php echo esc_attr( $index ); ?>][place]"
                                   value="<?php echo esc_attr( $event['place'] ); ?>"
                                   class="regular-text" />
                        </td>
                        <td>
                            <textarea name="fah_events[<?php echo esc_attr( $index ); ?>][description]" rows="2" class="large-text"><?php echo esc_textarea( $event['description'] ); ?></textarea>
                        </td>
                    </tr>
                    <?php
                endforeach;
            endif;
            ?>
            <!-- Empty template row for adding new events -->
            <tr class="fah-event-empty-row">
                <td>
                    <select name="fah_events[new_index][event_type]">
                        <?php foreach ( $event_types as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>">
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="fah_events[new_index][date_text]" class="regular-text" /></td>
                <td><input type="text" name="fah_events[new_index][place]" class="regular-text" /></td>
                <td><textarea name="fah_events[new_index][description]" rows="2" class="large-text"></textarea></td>
            </tr>
            </tbody>
        </table>

        <p>
            <button type="button" class="button" id="fah-add-event-row">
                <?php esc_html_e( 'Add another event', 'fah-genealogy' ); ?>
            </button>
        </p>

        <script>
            (function() {
                document.addEventListener('DOMContentLoaded', function() {
                    var table = document.getElementById('fah-events-table');
                    if (!table) return;

                    var addButton = document.getElementById('fah-add-event-row');
                    if (!addButton) return;

                    var emptyRow = table.querySelector('tr.fah-event-empty-row');
                    if (!emptyRow) return;

                    var tbody = table.querySelector('tbody');
                    var rowIndex = <?php echo ! empty( $events ) ? (int) count( $events ) : 0; ?>;

                    addButton.addEventListener('click', function() {
                        var clone = emptyRow.cloneNode(true);
                        clone.classList.remove('fah-event-empty-row');

                        var html = clone.innerHTML.replace(/new_index/g, rowIndex);
                        clone.innerHTML = html;

                        tbody.appendChild(clone);
                        rowIndex++;
                    });
                });
            })();
        </script>
        <?php
    }

    /**
     * Render Family Links meta box (father, mother, spouse).
     */
    public static function render_person_relationships_meta_box( $post ) {
        $father_id = FAH_Gene_Relationships::get_single_related( $post->ID, 'father' );
        $mother_id = FAH_Gene_Relationships::get_single_related( $post->ID, 'mother' );
        $spouse_id = FAH_Gene_Relationships::get_single_related( $post->ID, 'spouse' );

        $people = get_posts(
            array(
                'post_type'      => 'gene_person',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
                'fields'         => 'ids',
            )
        );

        // Build a simple id => title map.
        $person_options = array();
        if ( $people ) {
            foreach ( $people as $person_id ) {
                if ( (int) $person_id === (int) $post->ID ) {
                    continue; // Don't show self.
                }
                $person_options[ $person_id ] = get_the_title( $person_id );
            }
        }

        ?>
        <p><em><?php esc_html_e( 'Link this person to their close family members. More complex relationships will come later.', 'fah-genealogy' ); ?></em></p>

        <p>
            <label for="fah_father_id"><strong><?php esc_html_e( 'Father', 'fah-genealogy' ); ?></strong></label><br/>
            <select name="fah_father_id" id="fah_father_id" class="widefat">
                <option value=""><?php esc_html_e( '— Select person —', 'fah-genealogy' ); ?></option>
                <?php
                foreach ( $person_options as $id => $name ) :
                    ?>
                    <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $father_id, $id ); ?>>
                        <?php echo esc_html( $name ); ?>
                    </option>
                    <?php
                endforeach;
                ?>
            </select>
        </p>

        <p>
            <label for="fah_mother_id"><strong><?php esc_html_e( 'Mother', 'fah-genealogy' ); ?></strong></label><br/>
            <select name="fah_mother_id" id="fah_mother_id" class="widefat">
                <option value=""><?php esc_html_e( '— Select person —', 'fah-genealogy' ); ?></option>
                <?php
                foreach ( $person_options as $id => $name ) :
                    ?>
                    <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $mother_id, $id ); ?>>
                        <?php echo esc_html( $name ); ?>
                    </option>
                    <?php
                endforeach;
                ?>
            </select>
        </p>

        <p>
            <label for="fah_spouse_id"><strong><?php esc_html_e( 'Spouse / Partner', 'fah-genealogy' ); ?></strong></label><br/>
            <select name="fah_spouse_id" id="fah_spouse_id" class="widefat">
                <option value=""><?php esc_html_e( '— Select person —', 'fah-genealogy' ); ?></option>
                <?php
                foreach ( $person_options as $id => $name ) :
                    ?>
                    <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $spouse_id, $id ); ?>>
                        <?php echo esc_html( $name ); ?>
                    </option>
                    <?php
                endforeach;
                ?>
            </select>
        </p>
        <?php
    }

    /**
     * Save Person meta, events and relationships, keep title/slug in sync.
     */
    public static function save_person_meta( $post_id, $post ) {
        if ( ! isset( $_POST['fah_person_meta_nonce'] ) || ! wp_verify_nonce( $_POST['fah_person_meta_nonce'], 'fah_save_person_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( $post->post_type !== 'gene_person' ) {
            return;
        }

        // Core person fields.
        $given_names = isset( $_POST['fah_given_names'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_given_names'] ) ) : '';
        $surname     = isset( $_POST['fah_surname'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_surname'] ) ) : '';
        $gender      = isset( $_POST['fah_gender'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_gender'] ) ) : '';
        $birth_date  = isset( $_POST['fah_birth_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_birth_date'] ) ) : '';
        $birth_place = isset( $_POST['fah_birth_place'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_birth_place'] ) ) : '';
        $death_date  = isset( $_POST['fah_death_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_death_date'] ) ) : '';
        $death_place = isset( $_POST['fah_death_place'] ) ? sanitize_text_field( wp_unslash( $_POST['fah_death_place'] ) ) : '';
        $is_living   = isset( $_POST['fah_is_living'] ) ? '1' : '0';

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

        // Events.
        $events = isset( $_POST['fah_events'] ) && is_array( $_POST['fah_events'] ) ? $_POST['fah_events'] : array();
        FAH_Gene_Events::replace_events_for_person( $post_id, $events );

        // Relationships (father, mother, spouse).
        $father_id = isset( $_POST['fah_father_id'] ) ? (int) $_POST['fah_father_id'] : 0;
        $mother_id = isset( $_POST['fah_mother_id'] ) ? (int) $_POST['fah_mother_id'] : 0;
        $spouse_id = isset( $_POST['fah_spouse_id'] ) ? (int) $_POST['fah_spouse_id'] : 0;

        FAH_Gene_Relationships::set_core_family_links( $post_id, $father_id, $mother_id, $spouse_id );

        // Keep title/slug in sync.
        $full_name = trim( $given_names . ' ' . $surname );

        if ( ! empty( $full_name ) && $post->post_title !== $full_name ) {
            remove_action( 'save_post_gene_person', array( __CLASS__, 'save_person_meta' ), 10 );

            wp_update_post(
                array(
                    'ID'         => $post_id,
                    'post_title' => $full_name,
                    'post_name'  => sanitize_title( $full_name ),
                )
            );

            add_action( 'save_post_gene_person', array( __CLASS__, 'save_person_meta' ), 10, 2 );
        }
    }
}
