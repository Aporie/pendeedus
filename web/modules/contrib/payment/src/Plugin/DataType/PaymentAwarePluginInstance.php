<?php

namespace Drupal\payment\Plugin\DataType;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\PaymentAwareInterface;
use Drupal\plugin\Plugin\DataType\PluginInstance;

/**
 * Provides a payment-aware plugin instance data type.
 *
 * @DataType(
 *   id = "payment_aware_plugin_instance",
 *   label = @Translation("Plugin instance (payment-aware)")
 * )
 */
class PaymentAwarePluginInstance extends PluginInstance {

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    if ($value instanceof PaymentAwareInterface) {
      $data = $this;
      while ($data = $data->getParent()) {
        if ($data instanceof PaymentInterface) {
          $value->setPayment($data);
          break;
        }
      }
    }
    parent::setValue($value, $notify);
  }

}
