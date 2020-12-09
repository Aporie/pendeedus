/**
 * @file
 * Requests js behaviors.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.requests = {
    attach: function (context, settings) {
      if (context == document) {
        setInterval(() => {
          var params = {
            area: $('.form-select').eq(0).val() != undefined ? $('.form-select').eq(0).val() : 'All',
            county: $('.form-select').eq(1).val() != undefined ? $('.form-select').eq(1).val() : 'All',
            status: $('.form-select').eq(2).val() != undefined ? $('.form-select').eq(2).val() : 'All'
          }
          const searchParams = new URLSearchParams(params);
          
          Drupal.ajax({ url: drupalSettings.pd_request.refresh_path + '?' + searchParams.toString() }).execute();
        }, 30000);
      }
    }
  }

})(jQuery, Drupal);
