<?php
require_once 'classes/widgets.php';

/**
 * Registers the 'cities' custom post type.
 *
 * This function creates a custom post type for 'cities', which can be used to add city posts in WordPress.
 * The custom post type supports various features like title, editor, comments, and custom fields.
 * It also uses a custom taxonomy ('countries') to categorize the cities.
 *
 * @return void
 */
function create_custom_post_type() {
    register_post_type( 'cities',
        array(
            'labels' => array(
                'name' => __( 'Cities' ),
                'singular_name' => __( 'City' ),
            ),
            'public' => true,
            'has_archive' => false,
            'rewrite' => array('slug' => 'cities'),
            'show_in_rest' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'can_export' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'supports' => [ 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields' ],
            'taxonomies' => [ 'countries' ],
        )
    );
}
add_action( 'init', 'create_custom_post_type' );

/**
 * Registers the 'countries' taxonomy for the 'cities' custom post type.
 *
 * This function creates a custom taxonomy called 'countries' which allows users to categorize cities by country.
 * The taxonomy supports a hierarchical structure (like categories), and is also available in the REST API for compatibility
 * with the Gutenberg editor.
 *
 * @return void
 */
function create_cities_taxonomy() {
    $tax_labels = array(
        'name' => _x( 'Countries', 'plural' ),
        'singular_name' => _x( 'Country', 'singular' ),
        'search_items' => __( 'Search Countries' ),
        'all_items' => __( 'All Countries' ),
        'parent_item' => __( 'Parent Country' ),
        'parent_item_colon' => __( 'Parent Country:' ),
        'edit_item' => __( 'Edit Country' ),
        'update_item' => __( 'Update Country' ),
        'add_new_item' => __( 'Add New Country' ),
        'new_item_name' => __( 'New Country Name' ),
        'menu_name' => __( 'Countries' ),
    );

    register_taxonomy('countries', array('cities'), array(
        'hierarchical' => true,
        'labels' => $tax_labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'country' ),
    ));
}
add_action( 'init', 'create_cities_taxonomy', 0 );

/**
 * Adds the 'Coordinate' metabox to the 'cities' post type.
 *
 * This function creates a custom metabox titled 'Coordinate' on the 'cities' post type edit page.
 * The metabox contains fields for latitude and longitude, which are used to store geographical data for each city.
 *
 * @return void
 */
function coordinate_metabox() {
    add_meta_box(
        'coordinate_metabox',        // Metabox ID
        'Coordinate',                // Title of the metabox
        'coordinate_metabox_callback', // Callback function to display the form
        'cities',                    // Post type
        'normal',                    // Location of the metabox
        'high'                       // Priority of the metabox
    );
}
add_action('add_meta_boxes', 'coordinate_metabox');

/**
 * Callback function to render the 'Coordinate' metabox fields.
 *
 * This function is used to display the input fields for latitude and longitude inside the metabox.
 * The existing latitude and longitude values are pre-filled if available.
 *
 * @param WP_Post $post The current post object.
 * @return void
 */
function coordinate_metabox_callback($post) {
    wp_nonce_field('save_coordinate_data', 'coordinate_nonce');
    $latitude = get_post_meta($post->ID, '_latitude', true);
    $longitude = get_post_meta($post->ID, '_longitude', true);
    ?>
    <p>
        <label for="latitude">Latitude</label><br>
        <input type="text" name="latitude" id="latitude" value="<?php echo esc_attr($latitude); ?>" />
    </p>
    <p>
        <label for="longitude">Longitude</label><br>
        <input type="text" name="longitude" id="longitude" value="<?php echo esc_attr($longitude); ?>" />
    </p>
    <?php
}

/**
 * Saves latitude and longitude data from the 'Coordinate' metabox.
 *
 * This function saves the latitude and longitude data entered in the 'Coordinate' metabox.
 * It also verifies the nonce for security and prevents saving data during autosave.
 *
 * @param int $post_id The ID of the post being saved.
 * @return int The post ID.
 */
function save_coordinate_metabox_data($post_id) {
    if (!isset($_POST['coordinate_nonce']) || !wp_verify_nonce($_POST['coordinate_nonce'], 'save_coordinate_data')) {
        return $post_id;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if ('cities' != $_POST['post_type']) {
        return $post_id;
    }

    if (isset($_POST['latitude'])) {
        update_post_meta($post_id, '_latitude', sanitize_text_field($_POST['latitude']));
    }

    if (isset($_POST['longitude'])) {
        update_post_meta($post_id, '_longitude', sanitize_text_field($_POST['longitude']));
    }

    return $post_id;
}
add_action('save_post', 'save_coordinate_metabox_data');

/**
 * Registers the Weather_Widget widget.
 *
 * This function hooks into WordPress' `widgets_init` action to register the `Weather_Widget` class,
 * which will allow the widget to be available for use in WordPress widgets areas.
 *
 * @return void
 */
function register_weather_widget() {
    register_widget('Weather_Widget');
}
add_action('widgets_init', 'register_weather_widget');



