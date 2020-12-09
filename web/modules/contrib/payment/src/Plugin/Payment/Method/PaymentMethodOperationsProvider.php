<?php

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Core\Url;
use Drupal\plugin\PluginType\DefaultPluginTypeOperationsProvider;

/**
 * Provides operations for the payment method plugin type.
 */
class PaymentMethodOperationsProvider extends DefaultPluginTypeOperationsProvider {

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_type_id) {
    $operations = parent::getOperations($plugin_type_id);
    $operations['list']['url'] = new Url('payment.payment_method.collection');

    return $operations;
  }

}
