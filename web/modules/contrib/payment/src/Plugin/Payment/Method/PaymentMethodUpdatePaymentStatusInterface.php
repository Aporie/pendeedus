<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\PaymentMethodUpdatePaymentStatusInterface.
 */

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;

/**
 * Defines a payment method that controls payment status updates.
 *
 * Users can update payment statuses if they have the
 * "payment.payment.update_status.any" and/or
 * "payment.payment.update_status.own" permissions. By implementing this
 * interface, payment methods can exercise additional control on top of these
 * permissions.
 */
interface PaymentMethodUpdatePaymentStatusInterface {

  /**
   * Checks if the payment status can be updated.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function updatePaymentStatusAccess(AccountInterface $account);

  /**
   * Returns the statuses that can be set on a payment.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment to set the status on.
   *
   * @return string[]
   *   The plugin IDs of the settable statuses.
   */
  public function getSettablePaymentStatuses(AccountInterface $account, PaymentInterface $payment);

}
