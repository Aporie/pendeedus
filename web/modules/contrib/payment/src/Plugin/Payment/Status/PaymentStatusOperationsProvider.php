<?php

namespace Drupal\payment\Plugin\Payment\Status;

use Drupal\Core\Url;
use Drupal\plugin\PluginType\DefaultPluginTypeOperationsProvider;

/**
 * Provides operations for the payment status plugin type.
 */
class PaymentStatusOperationsProvider extends DefaultPluginTypeOperationsProvider {

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_type_id) {
    $operations = parent::getOperations($plugin_type_id);
    $operations['list']['url'] = new Url('entity.payment_status.collection');

    return $operations;
  }

}
