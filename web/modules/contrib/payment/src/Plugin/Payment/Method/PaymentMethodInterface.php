<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface.
 */

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\PaymentAwareInterface;

/**
 * Defines a payment method.
 *
 * PluginFormInterface is used to configure the plugin for a payment. The form
 * is embedded in another form, so self::submitForm() must only save form
 * values to $this and not redirect the page, for instance.
 *
 * Plugins can additionally implement the following interfaces:
 * - \Drupal\Core\Plugin\PluginFormInterface
 * - \Drupal\payment\Plugin\Payment\Method\PaymentMethodUpdatePaymentStatusInterface:
 *   This interface lets payment methods limit if users can update payment's
 *   statuses, and if so, which statuses can be set.
 * - \Drupal\payment\Plugin\Payment\Method\PaymentMethodCapturePaymentInterface:
 *   This interface lets payment methods capture already authorized payments.
 * - \Drupal\Core\Cache\CacheableDependencyInterface
 * - \Drupal\Component\Plugin\ConfigurableInterface
 *   Required if the plugin has any internal configuration, so it can be
 *   exported for recreation of the plugin at a later time.
 */
interface PaymentMethodInterface extends PluginInspectionInterface, PaymentAwareInterface {

  /**
   * Checks if the payment can be executed.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *
   * @see self::executePayment
   */
  public function executePaymentAccess(AccountInterface $account);

  /**
   * Executes the payment.
   *
   * When executing a payment, it may be authorized, or authorized and captured.
   * After calling this method, more action may be required depending on the
   * return value of self::getPaymentExecutionResult().
   * This method MUST set the payment's status to "payment_pending" before it
   * performs any payment-method-specific logic.
   *
   * @return \Drupal\payment\OperationResultInterface
   *
   * @see self::executePaymentAccess
   */
  public function executePayment();

  /**
   * Gets the payment execution status.
   *
   * @return \Drupal\payment\OperationResultInterface
   */
  public function getPaymentExecutionResult();

}
