<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodCapturePaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodRefundPaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodUpdatePaymentStatusInterface;

/**
 * Provides an access control handler for payment entities.
 */
class PaymentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $payment, $operation, AccountInterface $account) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */

    if ($operation == 'update_status') {
      $payment_method = $payment->getPaymentMethod();
      if ($payment_method instanceof PaymentMethodUpdatePaymentStatusInterface && !$payment_method->updatePaymentStatusAccess($account)) {
        return AccessResult::forbidden();
      }
    }
    elseif ($operation == 'capture') {
      $payment_method = $payment->getPaymentMethod();
      if ($payment_method instanceof PaymentMethodCapturePaymentInterface) {
        return AccessResult::allowedIf($payment_method instanceof PaymentMethodCapturePaymentInterface)
          ->andIf(AccessResult::allowedIf($payment_method->capturePaymentAccess($account)))
          ->andIf($this->checkAccessPermission($payment, $operation, $account));
      }
      return AccessResult::forbidden();
    }
    elseif ($operation == 'refund') {
      $payment_method = $payment->getPaymentMethod();
      if ($payment_method instanceof PaymentMethodRefundPaymentInterface) {
        return AccessResult::allowedIf($payment_method->refundPaymentAccess($account))
          ->andIf($this->checkAccessPermission($payment, $operation, $account));
      }
      return AccessResult::forbidden();
    }
    elseif ($operation == 'complete') {
      if ($payment->getPaymentMethod()) {
        return AccessResult::allowedIf($payment->getOwnerId() == $account->id())
          ->orIf(AccessResult::forbiddenIf($payment->getPaymentMethod()->getPaymentExecutionResult()->isCompleted()));
      }
      else {
        return AccessResult::forbidden();
      }
    }
    return $this->checkAccessPermission($payment, $operation, $account);
  }

  /**
   * Checks if a user has permission to perform a payment operation.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   * @param string $operation
   * @param \Drupal\Core\Session\AccountInterface
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkAccessPermission(PaymentInterface $payment, $operation, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'payment.payment.' . $operation . '.any')
      ->orIf(
        AccessResult::allowedIfHasPermission($account, 'payment.payment.' . $operation . '.own')
          ->andIf(AccessResult::allowedIf($account->id() == $payment->getOwnerId())->addCacheableDependency($payment))
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // We let other modules decide whether users have access to create
    // new payments. There is no corresponding permission for this operation.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCache($cid, $operation, $langcode, AccountInterface $account) {
    // Disable the cache, because the intensive operations are cached elsewhere
    // already and the results of all other operations are too volatile to be
    // cached.
  }
}
