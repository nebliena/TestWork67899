<?php
/**
 * Class Weather_Data
 *
 * This class is responsible for fetching weather data for each city based on latitude and longitude,
 * saving the data into a custom database table, and displaying it on the frontend.
 */
class Weather_Data {

    /**
     * Constructor method to initialize the class.
     */
    public function __construct() {
        // Hook into WordPress to fetch weather data periodically
        add_action('init', array($this, 'create_weather_table'));  // Create the table if it doesn't exist
        add_action('weather_data_update', array($this, 'update_weather_data_for_cities')); // Custom action to update weather data
        add_shortcode('weather_data_table', array($this, 'display_weather_data_table'));  // Shortcode to display weather data table
    }

    /**
     * Creates the weather_data table if it doesn't exist.
     *
     * This function is executed on 'init' to ensure the table is created when the plugin is activated.
     *
     * @return void
     */
    public function create_weather_table() {
        global $wpdb;

        // Table name
        $table_name = $wpdb->prefix . 'weather_data';

        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Create the table if it doesn't exist
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id INT(11) NOT NULL AUTO_INCREMENT,
                city_id INT(11) NOT NULL,
                temperature FLOAT NOT NULL,
                description VARCHAR(255) NOT NULL,
                humidity INT NOT NULL,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    /**
     * Fetches weather data from OpenWeatherMap and updates the database.
     *
     * This function retrieves weather data for each city post based on latitude and longitude, and updates
     * the weather_data table with the fetched data.
     *
     * @return void
     */
    public function update_weather_data_for_cities() {
        global $wpdb;

        // Get all cities posts
        $cities = get_posts(array(
            'post_type' => 'cities',
            'posts_per_page' => -1,
        ));

        // Loop through each city and fetch its weather data
        foreach ($cities as $city) {
            $latitude = get_post_meta($city->ID, '_latitude', true);
            $longitude = get_post_meta($city->ID, '_longitude', true);

            if ($latitude && $longitude) {
                $weather_data = $this->fetch_weather_data($latitude, $longitude);

                if ($weather_data) {
                    // Check if the data already exists in the weather_data table
                    $existing_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}weather_data WHERE city_id = %d", $city->ID));

                    if ($existing_data) {
                        // Update the data if it already exists
                        $wpdb->update(
                            $wpdb->prefix . 'weather_data',
                            array(
                                'temperature' => $weather_data['temp'],
                                'description' => $weather_data['description'],
                                'humidity' => $weather_data['humidity']
                            ),
                            array('city_id' => $city->ID)
                        );
                    } else {
                        // Insert new data if not exists
                        $wpdb->insert(
                            $wpdb->prefix . 'weather_data',
                            array(
                                'city_id' => $city->ID,
                                'temperature' => $weather_data['temp'],
                                'description' => $weather_data['description'],
                                'humidity' => $weather_data['humidity']
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Fetches weather data from OpenWeatherMap API.
     *
     * This function uses the latitude and longitude to fetch weather data from the OpenWeatherMap API.
     *
     * @param string $latitude  Latitude of the city.
     * @param string $longitude Longitude of the city.
     * @return array|false An array of weather data (temperature, description, humidity) on success, or false on failure.
     */
    private function fetch_weather_data($latitude, $longitude) {
        $api_key = 'YOUR_API_KEY'; // Replace with your OpenWeatherMap API key
        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&units=metric&appid={$api_key}";

        // Fetch weather data from the API
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && $data['cod'] == 200) {
            return array(
                'temp' => $data['main']['temp'],
                'description' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity']
            );
        }

        return false;
    }

    /**
     * Displays the weather data in a table format using a shortcode.
     *
     * This function generates and displays a table with all the weather data stored in the database.
     *
     * @return string The HTML table containing the weather data.
     */
    public function display_weather_data_table() {
        global $wpdb;

        // Get all weather data from the weather_data table
        $weather_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}weather_data");

        // Start output buffer to capture content
        ob_start();

        // Trigger before the table hook
        do_action('before_weather_data_table');

        // Check if data exists
        if ($weather_data) {
            ?>
            <table class="weather-data-table">
                <thead>
                    <tr>
                        <th>City</th>
                        <th>Temperature (°C)</th>
                        <th>Weather</th>
                        <th>Humidity (%)</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weather_data as $data) : ?>
                        <?php
                        $city = get_post($data->city_id);
                        ?>
                        <tr>
                            <td><?php echo esc_html(get_the_title($city)); ?></td>
                            <td><?php echo esc_html($data->temperature); ?>°C</td>
                            <td><?php echo esc_html($data->description); ?></td>
                            <td><?php echo esc_html($data->humidity); ?>%</td>
                            <td><?php echo esc_html($data->last_updated); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>No weather data available.</p>';
        }

        // Trigger after the table hook
        do_action('after_weather_data_table');

        // Return the output buffer content
        return ob_get_clean();
    }
}

// Initialize the Weather_Data class
$weather_data = new Weather_Data();

// Schedule the weather data update task
if (!wp_next_scheduled('weather_data_update')) {
    wp_schedule_event(time(), 'hourly', 'weather_data_update');
}