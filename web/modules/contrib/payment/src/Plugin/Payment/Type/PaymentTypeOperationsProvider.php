<?php

namespace Drupal\payment\Plugin\Payment\Type;

use Drupal\Core\Url;
use Drupal\plugin\PluginType\DefaultPluginTypeOperationsProvider;

/**
 * Provides operations for the payment type plugin type.
 */
class PaymentTypeOperationsProvider extends DefaultPluginTypeOperationsProvider {

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_type_id) {
    $operations = parent::getOperations($plugin_type_id);
    $operations['list']['url'] = new Url('payment.payment_type.collection');

    return $operations;
  }

}
