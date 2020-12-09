<?php

/**
 * Contains \Drupal\payment_test\Plugin\Payment\Method\PaymentTest.
 */

namespace Drupal\payment_test\Plugin\Payment\Method;

use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;

/**
 * A testing payment method.
 *
 * @PaymentMethod(
 *   id = "payment_test_no_response",
 *   label = @Translation("Test method (execution does not return response)"),
 *   message_text = "Foo",
 *   message_text_format = "plain_text"
 * )
 */
class PaymentTestNoResponse extends PaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  protected function getSupportedCurrencies() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecutePayment() {
    $this->getPayment()->setPaymentStatus($this->paymentStatusManager->createInstance('payment_success'));
    $this->getPayment()->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function doCapturePayment() {
  }

  /**
   * {@inheritdoc}
   */
  protected function doCapturePaymentAccess(AccountInterface $account) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doRefundPayment() {
  }

  /**
   * {@inheritdoc}
   */
  protected function doRefundPaymentAccess(AccountInterface $account) {
    return FALSE;
  }

}
