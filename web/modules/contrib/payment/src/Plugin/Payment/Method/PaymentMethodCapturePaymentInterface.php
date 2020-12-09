<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\PaymentMethodCapturePaymentInterface.
 */

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines a payment method that can capture authorized payments.
 *
 * Users can refund payments if they have the "payment.payment.capture.any"
 * permissions and self::capturePaymentAccess() returns TRUE.
 */
interface PaymentMethodCapturePaymentInterface {

  /**
   * Checks if the payment can be captured.
   *
   * The payment method must have been configured and the payment must have been
   * authorized prior to capture.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *
   * @see self::capturePayment
   */
  public function capturePaymentAccess(AccountInterface $account);

  /**
   * Captures the payment.
   *
   * Implementations must dispatch the
   * \Drupal\payment\Event\PaymentEvents::PAYMENT_PRE_CAPTURE Symfony event
   * before capture.
   *
   * @return \Drupal\payment\OperationResultInterface
   *
   * @see self::capturePaymentAccess
   */
  public function capturePayment();

  /**
   * Gets the payment capture status.
   *
   * @return \Drupal\payment\OperationResultInterface
   */
  public function getPaymentCaptureResult();

}
