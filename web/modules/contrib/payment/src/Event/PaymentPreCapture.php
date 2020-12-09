<?php

namespace Drupal\payment\Event;

use Drupal\payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Provides an event that is dispatched before a payment is captured.
 *
 * @see \Drupal\payment\Event\PaymentEvents::PAYMENT_PRE_CAPTURE
 */
class PaymentPreCapture extends Event {

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface
   */
  protected $payment;

  /**-
   * Constructs a new instance.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that will be captured.
   *
   * @param \Drupal\Core\Session\AccountInterface
   */
  public function __construct(PaymentInterface $payment) {
    $this->payment = $payment;
  }

  /**
   * Gets the payment that will be captured.
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  public function getPayment() {
    return $this->payment;
  }

}
