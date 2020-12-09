<?php

/**
 * Contains \Drupal\payment_test\Plugin\Payment\Type\PaymentTest.
 */

namespace Drupal\payment_test\Plugin\Payment\Type;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeBase;

/**
 * A testing payment type.
 *
 * @PaymentType(
 *   id = "payment_test",
 *   label = @Translation("Test type")
 * )
 */
class PaymentTest extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getPaymentDescription() {
    return 'The commander promoted Dirkjan to Major Failure.';
  }

  /**
   * {@inheritdoc
   */
  public function resumeContextAccess(AccountInterface $account) {
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc
   */
  public function doGetResumeContextResponse() {
  }

}
