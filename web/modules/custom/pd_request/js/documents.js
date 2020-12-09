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
        Drupal.ajax({ url: drupalSettings.pd_request.refresh_path }).execute();
      }, 30000);
    }

  }
})(jQuery, Drupal);
