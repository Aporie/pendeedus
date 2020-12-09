<?php

namespace Drupal\payment\Event;

use Drupal\payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Provides an event that is dispatched before the payment type's original
 * context is resumed.
 *
 * @see \Drupal\payment\Event\PaymentEvents::PAYMENT_TYPE_PRE_RESUME_CONTEXT
 */
class PaymentTypePreResumeContext extends Event {

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
   *   The payment for which the context will be resumed
   */
  public function __construct(PaymentInterface $payment) {
    $this->payment = $payment;
  }

  /**
   * Gets the payment for which the context will be resumed.
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  public function getPayment() {
    return $this->payment;
  }

}
