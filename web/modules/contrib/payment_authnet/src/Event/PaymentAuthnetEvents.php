<?php

namespace Drupal\payment_authnet\Event;

/**
 * Defines Payment events.
 */
final class PaymentAuthnetEvents {

  /**
   * The name of the event that is fired before a payment is refunded.
   *
   * @see \Drupal\payment_authnet\Event\PaymentPreVoid
   */
  const PAYMENT_AUTHNET_PRE_VOID = 'drupal.payment.payment_pre_void';

}
