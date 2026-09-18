<?php
// Check user capabilities
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

// Get options
$options = get_option( 'sitelift_opts' );
$api_key = isset( $options['api_key'] ) ? esc_attr( $options['api_key'] ) : '';

// Handle search query
$results = array();
if ( isset( $_POST['search_text'] ) ) {
    // Verify nonce
    if ( ! isset( $_POST['sitelift_nonce'] ) || ! wp_verify_nonce( $_POST['sitelift_nonce'], 'sitelift_places_finder' ) ) {
        wp_die( 'Invalid nonce.' );
    }

    $search_text = sanitize_text_field( $_POST['search_text'] );

    // Perform search using Sitelift_Places class
    $sitelift_places = new Sitelift_Places( $api_key );
    $search_results = $sitelift_places->search_places( $search_text );

    if ( $search_results && isset( $search_results['candidates'] ) ) {
        $results = $search_results['candidates'];
    }
}

?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <form method="post">
        <?php wp_nonce_field( 'sitelift_places_finder', 'sitelift_nonce' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Search for a Place', 'sitelift-grader' ); ?></th>
                <td>
                    <input type="text" name="search_text" class="regular-text">
                    <?php submit_button( 'Search', 'primary', 'submit', false ); ?>
                </td>
            </tr>
        </table>
    </form>

    <?php if ( ! empty( $results ) ) : ?>
        <h2>Search Results</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Place ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $results as $result ) : ?>
                    <tr>
                        <td><?php echo esc_html( $result['name'] ); ?></td>
                        <td><?php echo esc_html( $result['formatted_address'] ); ?></td>
                        <td><?php echo esc_html( $result['place_id'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
