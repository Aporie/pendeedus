<?php

namespace Drupal\payment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders a payment's amount.
 *
 * @ViewsField("payment_amount")
 */
class PaymentAmount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this->getEntity($values);

    return $payment->getCurrency()->formatAmount($payment->getAmount());
  }

}
