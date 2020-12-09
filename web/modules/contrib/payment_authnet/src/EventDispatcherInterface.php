<?php

namespace Drupal\payment_authnet;

use Drupal\payment\Entity\PaymentInterface;

/**
 * Defines a Payment event dispatcher.
 *
 * Because new events may be added in minor releases, this interface and all
 * classes that implemented are considered unstable forever. If you write an
 * event dispatcher, you must be prepared to update it in minor releases.
 */
interface EventDispatcherInterface {

  /**
   * Fires right before a payment will be canceled.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that will be canceled.
   */
  public function preVoidPayment(PaymentInterface $payment);

}
