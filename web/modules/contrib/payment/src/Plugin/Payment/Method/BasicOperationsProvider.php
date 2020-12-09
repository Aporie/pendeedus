<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\BasicOperationsProvider.
 */

namespace Drupal\payment\Plugin\Payment\Method;

/**
 * Provides payment_basic operations based on config entities.
 */
class BasicOperationsProvider extends PaymentMethodConfigurationOperationsProvider {

  /**
   * {@inheritdoc}
   */
  protected function getPaymentMethodConfiguration($plugin_id) {
    $entity_id = substr($plugin_id, 14);

    return $this->paymentMethodConfigurationStorage->load($entity_id);
  }

}
