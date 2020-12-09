<?php

namespace Drupal\payment\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Timestamp;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;

/**
 * Provides a payment status created data type.
 *
 * @DataType(
 *   id = "payment_status_created",
 *   label = @Translation("Payment status creation date/time")
 * )
 */
class PaymentStatusCreated extends Timestamp {

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    /** @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface $parent */
    $parent = $this->getParent();
    $plugin_instance = $parent->getContainedPluginInstance();
    if ($plugin_instance instanceof PaymentStatusInterface) {
      $plugin_instance->setCreated($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    /** @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface $parent */
    $parent = $this->getParent();
    $plugin_instance = $parent->getContainedPluginInstance();
    if ($plugin_instance instanceof PaymentStatusInterface) {
      return $plugin_instance->getCreated();
    }
    return NULL;
  }

}
