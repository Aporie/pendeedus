(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Refreshes this window's opener's payment references.
   */
  $(document).ready(function () {
    if (window.opener && window.opener.Drupal.PaymentReferenceRefreshButtons) {
      window.opener.Drupal.PaymentReferenceRefreshButtons();
    }
  });

  /**
   * Converts "close this window" messages to links.
   */
  Drupal.behaviors.PaymentReferenceWindowCloseLink = {
    attach: function (context) {
      if (window.opener) {
        $('span.payment_reference-window-close').each(function () {
          $(this).replaceWith('<a href="#" class="payment_reference-window-close">' + this.innerHTML + '</a>');
        });
        $('a.payment_reference-window-close').bind('click', function () {
          window.opener.focus();
          window.close();
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
