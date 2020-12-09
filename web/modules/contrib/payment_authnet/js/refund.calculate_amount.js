/**
 * @file
 * Calculates refund amount on Refund form for Payments with Authnet method.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Calculate total refund amount based on line items values.
   */
  Drupal.behaviors.paymentAuthnetRefundAmount = {
    attach: function (context, settings) {
      let $line_items = $('#' + settings.paymentAuthnet.lineItemsId, context).find('input[type="number"]')
        , $refund_amount = $('#' + settings.payment_authnet.refundAmountId, context)
        , calculator;

      if ($line_items.length && $refund_amount.length) {
        (calculator = function () {
          let sum = 0;
          $line_items.each(function () {
            sum = (sum * 100 + parseFloat($(this).val() ? $(this).val() : 0) * 100) / 100;
          });
          $refund_amount.val(sum.toFixed(2));
        })();

        $line_items.on('change keyup mouseup', calculator);
      }

    }
  };

})(jQuery, Drupal);
