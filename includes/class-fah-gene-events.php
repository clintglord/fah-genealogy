<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Event CRUD for FAH Genealogy.
 */
class FAH_Gene_Events {

    /**
     * Get all events for a person ordered by date_sort then id.
     *
     * @param int $person_id
     * @return array
     */
    public static function get_events_for_person( $person_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'fah_events';

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE person_id = %d ORDER BY date_sort ASC, id ASC",
            $person_id
        );

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Replace all events for a person with the given set.
     *
     * @param int   $person_id
     * @param array $events Posted events: each an array with event_type, date_text, place, description.
     */
    public static function replace_events_for_person( $person_id, $events ) {
        global $wpdb;
        $table = $wpdb->prefix . 'fah_events';

        $person_id = (int) $person_id;

        // Delete existing events for this person.
        $wpdb->delete( $table, array( 'person_id' => $person_id ), array( '%d' ) );

        if ( empty( $events ) || ! is_array( $events ) ) {
            return;
        }

        foreach ( $events as $event ) {
            $event_type  = isset( $event['event_type'] ) ? sanitize_text_field( $event['event_type'] ) : '';
            $date_text   = isset( $event['date_text'] ) ? sanitize_text_field( $event['date_text'] ) : '';
            $place       = isset( $event['place'] ) ? sanitize_text_field( $event['place'] ) : '';
            $description = isset( $event['description'] ) ? sanitize_textarea_field( $event['description'] ) : '';

            // Skip completely empty rows.
            if ( '' === $event_type && '' === $date_text && '' === $place && '' === $description ) {
                continue;
            }

            $date_sort = self::derive_sort_date( $date_text );

            $wpdb->insert(
                $table,
                array(
                    'person_id'   => $person_id,
                    'event_type'  => $event_type,
                    'date_text'   => $date_text,
                    'date_sort'   => $date_sort,
                    'place'       => $place,
                    'description' => $description,
                ),
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                )
            );
        }
    }

    /**
     * Very simple date_sort derivation from a free-text date.
     *
     * We'll try to parse YYYY, YYYY-MM, or YYYY-MM-DD, otherwise 0.
     *
     * @param string $date_text
     * @return int
     */
    protected static function derive_sort_date( $date_text ) {
        $date_text = trim( $date_text );
        if ( '' === $date_text ) {
            return 0;
        }

        // Extract something that looks like a year first.
        if ( preg_match( '/(\d{4})/', $date_text, $matches ) ) {
            $year = (int) $matches[1];
        } else {
            return 0;
        }

        $month = 1;
        $day   = 1;

        // Try to match patterns like YYYY-MM or YYYY-MM-DD.
        if ( preg_match( '/(\d{4})[-\/](\d{1,2})(?:[-\/](\d{1,2}))?/', $date_text, $m ) ) {
            $year = (int) $m[1];
            $month = isset( $m[2] ) ? (int) $m[2] : 1;
            $day   = isset( $m[3] ) ? (int) $m[3] : 1;
        }

        // Simple YYYYMMDD as integer.
        return (int) sprintf( '%04d%02d%02d', $year, $month, $day );
    }

    /**
     * List of core event types for dropdowns.
     *
     * @return array key => label
     */
    public static function get_event_types() {
        return array(
            ''           => __( '— Select event type —', 'fah-genealogy' ),
            'birth'      => __( 'Birth', 'fah-genealogy' ),
            'baptism'    => __( 'Baptism / Christening', 'fah-genealogy' ),
            'marriage'   => __( 'Marriage', 'fah-genealogy' ),
            'death'      => __( 'Death', 'fah-genealogy' ),
            'burial'     => __( 'Burial / Cremation', 'fah-genealogy' ),
            'residence'  => __( 'Residence', 'fah-genealogy' ),
            'occupation' => __( 'Occupation', 'fah-genealogy' ),
            'immigration'=> __( 'Immigration / Arrival', 'fah-genealogy' ),
            'emigration' => __( 'Emigration / Departure', 'fah-genealogy' ),
            'other'      => __( 'Other', 'fah-genealogy' ),
        );
    }
}
