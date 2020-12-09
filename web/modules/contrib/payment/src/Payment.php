<?php

namespace Drupal\payment;

/**
 * Provides wrappers for services.
 */
class Payment {

  /**
   * Returns the payment method manager.
   *
   * @return \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface
   */
  public static function methodManager() {
    return \Drupal::service('plugin.manager.payment.method');
  }

  /**
   * Returns the payment method configuration manager.
   *
   * @return \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface
   */
  public static function methodConfigurationManager() {
    return \Drupal::service('plugin.manager.payment.method_configuration');
  }

  /**
   * Returns the payment line item manager.
   *
   * @return \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface
   */
  public static function lineItemManager() {
    return \Drupal::service('plugin.manager.payment.line_item');
  }

  /**
   * Returns the payment status manager.
   *
   * @return \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  public static function statusManager() {
    return \Drupal::service('plugin.manager.payment.status');
  }

  /**
   * Returns the payment type manager.
   *
   * @return \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface
   */
  public static function typeManager() {
    return \Drupal::service('plugin.manager.payment.type');
  }

}
