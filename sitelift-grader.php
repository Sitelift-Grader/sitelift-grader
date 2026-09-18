<?php
/**
 * Plugin Name: SiteLift Grader
 * Description: Displays Google Places reviews and provides an integrated Places Finder with transient caching.
 * Version: 1.0.0
 * Author: Sitelift-Grader
 * Author URI: https://github.com/Sitelift-Grader
 * License: MIT
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SITELIFT_GRADER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SITELIFT_GRADER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once SITELIFT_GRADER_PLUGIN_DIR . 'includes/class-sitelift-places.php';

// Activation hook
register_activation_hook( __FILE__, 'sitelift_grader_activate' );
function sitelift_grader_activate() {
    // Initialize default options
    $default_options = array(
        'api_key' => '',
        'cache_ttl' => 3600, // 1 hour
        'place_id' => ''
    );
    add_option( 'sitelift_opts', $default_options );
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'sitelift_grader_deactivate' );
function sitelift_grader_deactivate() {
    // Remove the options on deactivation
    delete_option( 'sitelift_opts' );
}

// Admin menu
add_action( 'admin_menu', 'sitelift_grader_admin_menu' );
function sitelift_grader_admin_menu() {
    add_menu_page(
        'SiteLift',
        'SiteLift',
        'manage_options',
        'sitelift-grader',
        'sitelift_grader_settings_page',
        'dashicons-star-half',
        20
    );

    add_submenu_page(
        'sitelift-grader',
        'Places Finder',
        'Places Finder',
        'manage_options',
        'sitelift-places-finder',
        'sitelift_places_finder_page'
    );
}

// Settings page
function sitelift_grader_settings_page() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle option updates
    if ( isset( $_POST['sitelift_opts'] ) ) {
        // Verify nonce
        if ( ! isset( $_POST['sitelift_nonce'] ) || ! wp_verify_nonce( $_POST['sitelift_nonce'], 'sitelift_save_settings' ) ) {
            wp_die( 'Invalid nonce.' );
        }

        // Sanitize input
        $options = array();
        $options['api_key'] = sanitize_text_field( $_POST['sitelift_opts']['api_key'] );
        $options['cache_ttl'] = intval( $_POST['sitelift_opts']['cache_ttl'] );
        $options['place_id'] = sanitize_text_field( $_POST['sitelift_opts']['place_id'] );

        // Update options
        update_option( 'sitelift_opts', $options );

        // Display success message
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Settings saved.', 'sitelift-grader' ); ?></p>
        </div>
        <?php
    }

    // Get options
    $options = get_option( 'sitelift_opts' );
    $api_key = isset( $options['api_key'] ) ? esc_attr( $options['api_key'] ) : '';
    $cache_ttl = isset( $options['cache_ttl'] ) ? intval( $options['cache_ttl'] ) : 3600;
    $place_id = isset( $options['place_id'] ) ? esc_attr( $options['place_id'] ) : '';

    // Output settings form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'sitelift_save_settings', 'sitelift_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Google Places API Key', 'sitelift-grader' ); ?></th>
                    <td><input type="text" name="sitelift_opts[api_key]" value="<?php echo $api_key; ?>" class="regular-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Cache TTL (seconds)', 'sitelift-grader' ); ?></th>
                    <td><input type="number" name="sitelift_opts[cache_ttl]" value="<?php echo $cache_ttl; ?>" class="small-text"></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><?php _e( 'Place ID', 'sitelift-grader' ); ?></th>
                    <td><input type="text" name="sitelift_opts[place_id]" value="<?php echo $place_id; ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Places Finder page (Placeholder - content will be added in admin/page-places-finder.php)
function sitelift_places_finder_page() {
    require_once SITELIFT_GRADER_PLUGIN_DIR . 'admin/page-places-finder.php';
}

// Shortcode to display reviews
add_shortcode( 'sitelift_reviews', 'sitelift_reviews_shortcode' );
function sitelift_reviews_shortcode() {
    // Get options
    $options = get_option( 'sitelift_opts' );
    $api_key = isset( $options['api_key'] ) ? esc_attr( $options['api_key'] ) : '';
    $cache_ttl = isset( $options['cache_ttl'] ) ? intval( $options['cache_ttl'] ) : 3600;
    $place_id = isset( $options['place_id'] ) ? esc_attr( $options['place_id'] ) : '';

    // Enqueue CSS if shortcode is used
    wp_enqueue_style( 'sitelift-grader-widget-style', SITELIFT_GRADER_PLUGIN_URL . 'assets/widget.css' );

    // Get cached reviews
    $transient_key = 'sitelift_reviews_' . $place_id;
    $reviews_html = get_transient( $transient_key );

    if ( false === $reviews_html ) {
        // Fetch reviews from Google Places API
        $sitelift_places = new Sitelift_Places( $api_key );
        $place_details = $sitelift_places->get_place_details( $place_id );

        if ( ! empty( $place_details ) && isset( $place_details['reviews'] ) ) {
            $reviews = $place_details['reviews'];

            // Build HTML output
            $reviews_html = '<div class="sitelift-reviews-widget">';
            $reviews_html .= '<h3>Reviews</h3>';
            $reviews_html .= '<ul>';
            foreach ( $reviews as $review ) {
                $reviews_html .= '<li>';
                $reviews_html .= '<div class="author">' . esc_html( $review['author_name'] ) . '</div>';
                $reviews_html .= '<div class="rating">Rating: ' . intval( $review['rating'] ) . '</div>';
                $reviews_html .= '<div class="text">' . esc_html( $review['text'] ) . '</div>';
                $reviews_html .= '</li>';
            }
            $reviews_html .= '</ul>';
            $reviews_html .= '</div>';

            // Set transient
            set_transient( $transient_key, $reviews_html, $cache_ttl );
        } else {
            $reviews_html = '<p>Could not retrieve reviews.</p>';
        }
    }

    return $reviews_html;
}
