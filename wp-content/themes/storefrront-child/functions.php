<?php
require_once "includes/weatherapi-PHP/vendor/autoload.php";
require_once 'classes/widgets.php';
require_once 'classes/weather.data.php';

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
            'supports' => [ 'title', 'author','revisions', 'custom-fields' ],
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

function before_weather_data_table_callback() {
    echo '<h2>Weather Data Overview</h2>';
}
add_action('before_weather_data_table', 'before_weather_data_table_callback');

function after_weather_data_table_callback() {
    echo '<p>End of weather data list.</p>';
}
add_action('after_weather_data_table', 'after_weather_data_table_callback');

/**
 * Enqueues the JavaScript file for handling the AJAX search functionality.
 *
 * This function registers and localizes a JavaScript file that listens for input in the search field
 * and sends AJAX requests to fetch filtered weather data.
 *
 * @return void
 */
function enqueue_weather_search_script() {
    wp_enqueue_script(
        'weather-search',
        get_stylesheet_directory_uri() . '/assets/js/weather-search.js', // Replace with the correct path to your JS file
        array('jquery'),
        '1.0',
        true
    );

    // Localize the script with necessary AJAX data
    wp_localize_script('weather-search', 'weatherSearch', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('weather_search_nonce')
    ));
}

// Hook into WordPress to enqueue the script
add_action('wp_enqueue_scripts', 'enqueue_weather_search_script');


/**
 * AJAX handler for filtering weather data based on the city name.
 *
 * This function fetches weather data from the database based on the user's search input and returns the results
 * in HTML format. If no matching data is found, it returns a message indicating no results.
 *
 * @return void Outputs the filtered weather data as HTML or an error message in JSON format.
 */
function filter_weather_data() {
    global $wpdb;

    // Get the search query from the AJAX request
    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';

    // Fetch matching weather data from the database
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT wd.*, p.post_title 
            FROM {$wpdb->prefix}weather_data wd 
            JOIN {$wpdb->posts} p ON wd.city_id = p.ID 
            WHERE p.post_title LIKE %s",
            '%' . $wpdb->esc_like($search_query) . '%'
        )
    );

    // Check if results were found
    if ($results) {
        ob_start();
        ?>
        <table class="weather-data-table">
            <thead>
                <tr>
                    <th>City</th>
                    <th>Temperature (°C/°F)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $data) : ?>
                    <?php $tempObject = json_decode($data->temperature); ?>
                    <tr>
                        <td><?php echo esc_html($data->post_title); ?></td>
                        <td><?php echo esc_html($tempObject->temp[0]); ?>°C/<?php echo esc_html($tempObject->temp[1]); ?>°F</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        wp_send_json_success(ob_get_clean());
    } else {
        wp_send_json_success('<p>No matching weather data found.</p>');
    }

    wp_die();
}

// Register the AJAX actions
add_action('wp_ajax_filter_weather_data', 'filter_weather_data');
add_action('wp_ajax_nopriv_filter_weather_data', 'filter_weather_data');

// Initialize the Weather_Data class
$weather_data = new Weather_Data();
