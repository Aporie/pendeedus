/**
 * @file
 * Requests js behaviors.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.requests = {
    attach: function (context, settings) {
      if (context == document) {

        // Refresh view every 30 sec.
        setInterval(() => {
          var params = {
            area: $("select[id*='edit-field-area-target-id']").eq(0).val() != undefined ? $("select[id*='edit-field-area-target-id']").eq(0).val() : 'All',
            county: $("select[id*='edit-field-area-target-id']").eq(1).val() != undefined ? $("select[id*='edit-field-area-target-id']").eq(1).val() : 'All',
            status: $("select[id*='edit-status']").val() != undefined ? $("select[id*='edit-status']").val() : 'All'
          }
          const searchParams = new URLSearchParams(params);
          Drupal.ajax({ url: drupalSettings.pd_request.refresh_path + '?' + searchParams.toString() }).execute();
        }, 30000);
      }
    }
  }

})(jQuery, Drupal);
