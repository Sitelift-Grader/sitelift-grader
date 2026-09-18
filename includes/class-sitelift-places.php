<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sitelift_Places {

    private $api_key;

    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    public function get_place_details( $place_id ) {
        $api_url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . urlencode( $place_id ) . '&fields=reviews&key=' . urlencode( $this->api_key );

        $response = wp_remote_get( $api_url );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['result'] ) ) {
            return $data['result'];
        } else {
            return false;
        }
    }

    public function search_places( $search_text ) {
        // Implement the Places API "Search" functionality here.
        // This is a placeholder.  You'll need to use the Places API
        // "Find Place From Text" or "Nearby Search" endpoint.
        // See: https://developers.google.com/maps/documentation/places/web-service/search

        // Example using "Find Place From Text"
        $api_url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input=' . urlencode( $search_text ) . '&inputtype=textquery&fields=place_id,name,formatted_address&key=' . urlencode( $this->api_key );

        $response = wp_remote_get( $api_url );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return $data;
    }
}
