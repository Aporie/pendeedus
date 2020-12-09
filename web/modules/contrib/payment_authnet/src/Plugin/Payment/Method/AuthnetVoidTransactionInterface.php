<?php

namespace Drupal\payment_authnet\Plugin\Payment\Method;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines a payment method that can cancel Authorize.net payments.
 *
 * Users can cancel payments if they have the "payment.payment.void.any"
 * permissions and self::voidPaymentAccess() returns TRUE.
 */
interface AuthnetVoidTransactionInterface {

  /**
   * Checks if the payment can be cancelled.
   *
   * The payment method must have been configured and the payment must have been
   * captured prior to refunding it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result.
   *
   * @see self::voidPayment
   */
  public function voidPaymentAccess(AccountInterface $account);

  /**
   * Refunds the payment.
   *
   * Implementations must dispatch the
   * \Drupal\payment\Event\PaymentEvents::PAYMENT_PRE_REFUND Symfony event
   * before refunding the payment.
   *
   * @return \Drupal\payment\OperationResultInterface
   *   Operation result (empty response if completed).
   *
   * @see self::voidPaymentAccess
   */
  public function voidPayment();

  /**
   * Gets the payment refund status.
   *
   * @return \Drupal\payment\OperationResultInterface
   *   Operation result.
   */
  public function getPaymentVoidResult();

  /**
   * Gets the configuration to make totalAmount empty after cancel operation.
   *
   * @return bool
   *   True if total amount should be 0 after cancel operation, FALSE otherwise.
   */
  public function getCancelZeroAmount();

}
