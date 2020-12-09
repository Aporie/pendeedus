(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Binds a listener on dialog creation to handle the payment completion link.
   */
  $(window).on('dialog:aftercreate', function (e, dialog, $element, settings) {
    $element.on('click.dialog', '.payment-reference-complete-payment-link', function (e) {
      dialog.close('complete-payment');
    });
  });

  /**
   * Refreshes all payment references.
   */
  Drupal.PaymentReferenceRefreshButtons = function () {
    $('.payment_reference-refresh-button').each(function () {
      if (!drupalSettings.PaymentReferencePaymentAvailable[drupalSettings.ajax[this.id].wrapper]) {
        $(this).trigger('mousedown');
      }
    });
  };

  /**
   * Sets an interval to refresh all payment references.
   */
  setInterval(Drupal.PaymentReferenceRefreshButtons, 30000);

})(jQuery, Drupal, drupalSettings);
