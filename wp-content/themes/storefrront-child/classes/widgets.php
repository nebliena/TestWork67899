<?php
/**
 * Weather_Widget class.
 *
 * A custom widget for displaying weather information of a city based on latitude and longitude
 * using the OpenWeatherMap API. The widget allows selecting a city from a dropdown and shows the
 * weather information for the selected city.
 */
class Weather_Widget extends WP_Widget {

    /**
     * Constructor method for initializing the widget.
     *
     * This constructor initializes the widget with a unique ID, a name, and an optional description.
     * The widget will be used to display weather information for a selected city based on latitude and longitude.
     *
     * @return void
     */
    function __construct() {
        parent::__construct(
            'weather_widget',           // Base ID
            'Weather Widget',           // Name
            array('description' => 'A widget to display the weather for a selected city based on latitude and longitude') // Widget options
        );
    }

    /**
     * Outputs the widget form in the WordPress admin panel.
     *
     * This method is called in the admin to display the form fields needed to configure the widget.
     * It includes a dropdown for selecting a city.
     *
     * @param array $instance The current widget instance settings.
     * @return void
     */
    public function form($instance) {
        // Get selected city ID from the instance
        $selected_city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';
        
        // Get all cities posts
        $cities = get_posts(array(
            'post_type' => 'cities',
            'posts_per_page' => -1,
        ));

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('city_id'); ?>">Select City:</label>
            <select name="<?php echo $this->get_field_name('city_id'); ?>" id="<?php echo $this->get_field_id('city_id'); ?>" class="widefat">
                <option value="">Select a city</option>
                <?php foreach ($cities as $city) : ?>
                    <option value="<?php echo $city->ID; ?>" <?php selected($selected_city_id, $city->ID); ?>>
                        <?php echo esc_html(get_the_title($city->ID)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    /**
     * Updates widget settings.
     *
     * This method is called when the widget is updated in the WordPress admin.
     * It saves the new widget instance settings.
     *
     * @param array $new_instance The new instance settings.
     * @param array $old_instance The old instance settings.
     * @return array The updated instance settings.
     */
    public function update($new_instance, $old_instance) {
        // Save the selected city ID
        $instance = array();
        $instance['city_id'] = (!empty($new_instance['city_id'])) ? sanitize_text_field($new_instance['city_id']) : '';

        return $instance;
    }

    /**
     * Outputs the widget content on the frontend.
     *
     * This method is called when the widget is displayed on the site frontend. It fetches the
     * latitude and longitude of the selected city, retrieves the weather data from OpenWeatherMap,
     * and displays it.
     *
     * @param array $args The widget arguments, including before and after widget HTML.
     * @param array $instance The current widget instance settings.
     * @return void
     */
    public function widget($args, $instance) {
        $city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';
        
        if ($city_id) {
            // Get the latitude and longitude for the selected city
            $latitude = get_post_meta($city_id, '_latitude', true);
            $longitude = get_post_meta($city_id, '_longitude', true);

            if ($latitude && $longitude) {
                // Fetch the weather data from OpenWeatherMap
                $weather_data = $this->get_weather_data($latitude, $longitude);

                //var_dump($weather_data);die();
                
                echo $args['before_widget']; // Widget HTML before the content
                ?>
                <div class="weather-widget">
                    <h3>Weather for <?php echo esc_html(get_the_title($city_id)); ?></h3>
                    <?php if ($weather_data): ?>
                        <p><strong>Temperature:</strong> <?php echo esc_html($weather_data['temp']); ?>°C</p>
                        <p><strong>Weather:</strong> <?php echo esc_html($weather_data['description']); ?></p>
                        <p><strong>Humidity:</strong> <?php echo esc_html($weather_data['humidity']); ?>%</p>
                    <?php else: ?>
                        <p>Unable to fetch weather data at the moment.</p>
                    <?php endif; ?>
                </div>
                <?php
                echo $args['after_widget']; // Widget HTML after the content
            } else {
                echo $args['before_widget'];
                ?>
                <div class="weather-widget">
                    <p>No location data available for this city.</p>
                </div>
                <?php
                echo $args['after_widget'];
            }
        } else {
            echo $args['before_widget'];
            ?>
            <div class="weather-widget">
                <p>Please select a city to view weather information.</p>
            </div>
            <?php
            echo $args['after_widget'];
        }
    }

    /**
     * Fetches weather data from the OpenWeatherMap API.
     *
     * This function uses the latitude and longitude of a city to fetch weather data from the
     * OpenWeatherMap API, including temperature, description, and humidity.
     *
     * @param string $latitude  The latitude of the city.
     * @param string $longitude The longitude of the city.
     * @return array|false Weather data array on success, false on failure.
     */
    private function get_weather_data($latitude, $longitude) {
        $api_key = '2869cc01c302c7e2bc32238ce76740eb'; // Replace with your actual OpenWeatherMap API key
        $url = "https://api.openweathermap.org/data/2.5/onecall?lat={$latitude}&lon={$longitude}&units=metric&appid={$api_key}";

        // Fetch the weather data using the API
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && $data['cod'] == 200) {
            // Return temperature, weather description, and humidity
            return array(
                'temp' => $data['main']['temp'],
                'description' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity']
            );
        } else {
            return false;
        }
    }
}
