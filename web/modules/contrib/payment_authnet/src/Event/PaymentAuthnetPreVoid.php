<?php

namespace Drupal\payment_authnet\Event;

use Drupal\payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Provides an event that is dispatched before a payment is canceled (voided).
 *
 * @see \Drupal\payment_authnet\Event\PaymentAuthnetEvents::PAYMENT_AUTHNET_PRE_VOID
 */
class PaymentAuthnetPreVoid extends Event {

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that will be canceled.
   */
  public function __construct(PaymentInterface $payment) {
    $this->payment = $payment;
  }

  /**
   * Gets the payment that will be canceled.
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   *   Payment entity.
   */
  public function getPayment() {
    return $this->payment;
  }

}
