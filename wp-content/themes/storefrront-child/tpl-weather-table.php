<?php
/**
 * Template Name: Weather Table
 *
 * A custom page template to display weather data for cities in a table format.
 *
 * @package WordPress
 * @subpackage storefrront-child
 * @since 1.0
 */

 get_header();
?>
<div class="weather-table_container">
    <?php echo do_shortcode('[weather_data_table]');?>
</div>

<?php get_footer(); ?>