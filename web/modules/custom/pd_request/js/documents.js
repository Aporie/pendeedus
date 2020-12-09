/**
 * @file
 * Documents js behaviors.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.documents = {
    attach: function (context, settings) {
      // Make sure behavior is added only once.
      if (context !== document) {
        return;
      }

      // Refresh view every 30 sec.
      setInterval(() => { 
        $.ajax({
          url: drupalSettings.pd_request.refresh_path,
          method: 'POST',
          dataType: 'json',
        }).done((view) => {
          var content = $(view).find('.view-content');
          $('.view-content').replaceWith(content);
        }).fail(() => {
          console.error('Page refresh failed.')
        });
      }, 30000);
    }

  }
})(jQuery, Drupal);
