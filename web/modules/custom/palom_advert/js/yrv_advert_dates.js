/**
 * JS for YRV Advert dates.
 */
(function ($, Drupal, settings) {

    'use strict';

    Drupal.behaviors.yrv_advert_dates = {
        attach: function (context) {

            // Предварительная установка данных
            if (settings.advert_date_type > 0){
                $('#edit-field-advert-dates-wrapper').once('slide-up').slideUp();
            }

            $('#select_advert_date_types').on('change', function(evt){
                var advert_date_type = $(this).val();
                var $advert_dates_wrapper = $('#edit-field-advert-dates-wrapper');

                if (advert_date_type == 0){
                    $advert_dates_wrapper.slideDown();
                }
                else{
                    $advert_dates_wrapper.slideUp();
                }
            })
        }
    };
})(jQuery, Drupal, drupalSettings);