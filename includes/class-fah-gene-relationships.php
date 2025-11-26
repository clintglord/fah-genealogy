<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Relationship CRUD for FAH Genealogy.
 */
class FAH_Gene_Relationships {

    /**
     * Get related person ID for a given relationship type (single record).
     *
     * @param int    $person_id
     * @param string $relationship_type
     * @return int|null
     */
    public static function get_single_related( $person_id, $relationship_type ) {
        global $wpdb;
        $table = $wpdb->prefix . 'fah_relationships';

        $sql = $wpdb->prepare(
            "SELECT related_person_id FROM {$table} WHERE person_id = %d AND relationship_type = %s ORDER BY id ASC LIMIT 1",
            $person_id,
            $relationship_type
        );

        $result = $wpdb->get_var( $sql );

        return $result ? (int) $result : null;
    }

    /**
     * Set core family links (father, mother, spouse) for a person.
     *
     * This replaces existing records for those relationship types.
     *
     * @param int      $person_id
     * @param int|null $father_id
     * @param int|null $mother_id
     * @param int|null $spouse_id
     */
    public static function set_core_family_links( $person_id, $father_id, $mother_id, $spouse_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'fah_relationships';

        $person_id = (int) $person_id;

        // Delete existing core relationship rows for this person.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE person_id = %d AND relationship_type IN ('father','mother','spouse','partner')",
                $person_id
            )
        );

        // Insert new ones if set.
        if ( $father_id ) {
            $wpdb->insert(
                $table,
                array(
                    'person_id'         => $person_id,
                    'related_person_id' => (int) $father_id,
                    'relationship_type' => 'father',
                ),
                array( '%d', '%d', '%s' )
            );
        }

        if ( $mother_id ) {
            $wpdb->insert(
                $table,
                array(
                    'person_id'         => $person_id,
                    'related_person_id' => (int) $mother_id,
                    'relationship_type' => 'mother',
                ),
                array( '%d', '%d', '%s' )
            );
        }

        if ( $spouse_id ) {
            $wpdb->insert(
                $table,
                array(
                    'person_id'         => $person_id,
                    'related_person_id' => (int) $spouse_id,
                    'relationship_type' => 'spouse',
                ),
                array( '%d', '%d', '%s' )
            );
        }
    }
}
