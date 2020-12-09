<?php

/**
 * Contains \Drupal\payment\PaymentAwareInterface.
 */

namespace Drupal\payment;

use Drupal\payment\Entity\PaymentInterface;

/**
 * Defines an object that can be aware of a payment entity.
 */
interface PaymentAwareInterface {

  /**
   * Sets the payment.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *
   * @return $this
   */
  public function setPayment(PaymentInterface $payment);

  /**
   * Gets the payment.
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  public function getPayment();

}
