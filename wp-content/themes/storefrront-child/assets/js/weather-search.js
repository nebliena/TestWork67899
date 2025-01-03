(function($){
$(document).ready(function ($) {
        $('#weather-search').on('input', function () {
            const searchQuery = $(this).val();

            $.ajax({
                url: weatherSearch.ajax_url,
                type: 'POST',
                data: {
                    action: 'filter_weather_data',
                    search_query: searchQuery,
                    nonce: weatherSearch.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('#weather-data-results').html(response.data);
                    } else {
                        $('#weather-data-results').html('<p>Error loading data.</p>');
                    }
                },
                error: function () {
                    $('#weather-data-results').html('<p>Error loading data.</p>');
                }
            });
        });
    });
})(jQuery);