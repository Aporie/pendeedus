<?php

namespace Drupal\payment\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines a payment method configuration manager.
 */
interface PaymentMethodConfigurationManagerInterface extends PluginManagerInterface {

  /**
   * Creates a payment method configuration.
   *
   * @param string $plugin_id
   *   The id of the plugin being instantiated.
   * @param mixed[] $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationInterface
   */
  public function createInstance($plugin_id, array $configuration = []);

}
