<?php

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\PluginOperationsProviderProviderInterface;

/**
 * Defines a payment method manager.
 */
interface PaymentMethodManagerInterface extends PluginOperationsProviderProviderInterface, PluginManagerInterface {

  /**
   * Creates a payment method.
   *
   * @param string $plugin_id
   *   The id of the plugin being instantiated.
   * @param mixed[] $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface
   */
  public function createInstance($plugin_id, array $configuration = []);

}
