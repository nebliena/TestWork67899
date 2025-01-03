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
        add_action('init', array($this, 'update_weather_data_for_cities')); // get weather data
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
        $wpdb->show_errors = true;

        // Table name
        $table_name = $wpdb->prefix . 'weather_data';

        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Create the table if it doesn't exist
            $charset_collate = $wpdb->get_charset_collate();
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id INT(11) NOT NULL AUTO_INCREMENT,
                city_id INT(11) NOT NULL,
                temperature VARCHAR(24) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            //exit( var_dump( $wpdb->last_query ) );
        }
    }

    /**
     * Fetches weather data from WeatherAPI and updates the database.
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

                    //$tempObject = json_decode($weather_data);

                    if ($existing_data) {
                        // Update the data if it already exists
                        $wpdb->update(
                            $wpdb->prefix . 'weather_data',
                            array(
                                'temperature' => $weather_data
                            ),
                            array('city_id' => $city->ID)
                        );
                    } else {
                        // Insert new data if not exists
                        $wpdb->insert(
                            $wpdb->prefix . 'weather_data',
                            array(
                                'city_id' => $city->ID,
                                'temperature' => $weather_data
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Fetches weather data from WeatherAPI API.
     *
     * This function uses the latitude and longitude to fetch weather data from the WeatherAPI API.
     *
     * @param string $latitude  Latitude of the city.
     * @param string $longitude Longitude of the city.
     * @return array|false An array of weather data (temperature) on success, or false on failure.
     */
    private function fetch_weather_data($latitude, $longitude) {
        $api_key = 'e84cfa21c6fb4be6960141250250301';
        $client = new WeatherAPILib\WeatherAPIClient($api_key);
        $q = "{$latitude},{$longitude}";
        $aPIs = $client->getAPIs();
        $response = $aPIs->getRealtimeWeather($q);

        //var_dump($response);die();

        if (is_wp_error($response)) {
            return false;
        }

        if ($response) {
            // Return temperature
            $tempObject = json_encode(array(
                'temp' => [$response->current->tempC,$response->current->tempF]
            ));

            return $tempObject;
        } else {
            return false;
        }
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
    
        // Start output buffer to capture content
        ob_start();
    
        ?>
        <div class="weather-data-search">
            <input type="text" id="weather-search" placeholder="Search city...">
        </div>
        <div id="weather-data-results">
            <?php
            // Get all weather data from the weather_data table
            $weather_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}weather_data");
    
            // Trigger before the table hook
            do_action('before_weather_data_table');
    
            // Check if data exists
            if ($weather_data) {
                ?>
                <table class="weather-data-table">
                    <thead>
                        <tr>
                            <th>City</th>
                            <th>Temperature (°C/°F)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weather_data as $data) : ?>
                            <?php
                            $city = get_post($data->city_id);
                            $tempObject = json_decode($data->temperature);
                            ?>
                            <tr>
                                <td><?php echo esc_html(get_the_title($city)); ?></td>
                                <td><?php echo esc_html($tempObject->temp[0]); ?>°C/<?php echo esc_html($tempObject->temp[1]); ?>°F</td>
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
            ?>
        </div>
        <?php
    
        // Return the output buffer content
        return ob_get_clean();
    }
}