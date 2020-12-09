<?php

namespace Drupal\payment_authnet\Plugin\Payment\Method;

use Drupal\payment\Plugin\Payment\Method\PaymentMethodConfigurationOperationsProvider;

/**
 * Provides payment_authnet operations based on config entities.
 */
class AuthnetOperationsProvider extends PaymentMethodConfigurationOperationsProvider {

  /**
   * {@inheritdoc}
   */
  protected function getPaymentMethodConfiguration($plugin_id) {
    // Remove 'payment_authnet:' prefix to get config ID.
    $entity_id = substr($plugin_id, 16);

    return $this->paymentMethodConfigurationStorage->load($entity_id);
  }

}
