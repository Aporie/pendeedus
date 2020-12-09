<?php

/**
 * Contains \Drupal\payment\PaymentAwareTrait.
 */

namespace Drupal\payment;

use Drupal\payment\Entity\PaymentInterface;

/**
 * Provides a default implementation of \Drupal\payment\PaymentAwareInterface.
 */
trait PaymentAwareTrait {

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * {@inheritdoc}
   */
  public function getPayment() {
    return $this->payment;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayment(PaymentInterface $payment) {
    $this->payment = $payment;

    return $this;
  }

}
