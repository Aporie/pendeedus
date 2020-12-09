<?php

namespace Drupal\payment_reference;

/**
 * Provides wrappers for services.
 */
class PaymentReference {

  /**
   * Returns the payment factory.
   *
   * @return \Drupal\payment_reference\PaymentFactoryInterface
   */
  public static function factory() {
    return \Drupal::service('payment_reference.payment_factory');
  }

  /**
   * Returns the payment reference queue.
   *
   * @return \Drupal\payment\QueueInterface
   */
  public static function queue() {
    return \Drupal::service('payment_reference.queue');
  }

}
