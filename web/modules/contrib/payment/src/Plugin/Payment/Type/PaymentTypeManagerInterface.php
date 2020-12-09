<?php

namespace Drupal\payment\Plugin\Payment\Type;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\PluginOperationsProviderProviderInterface;

/**
 * Defines a payment type manager.
 */
interface PaymentTypeManagerInterface extends PluginOperationsProviderProviderInterface, PluginManagerInterface {

  /**
   * Creates a payment type.
   *
   * @param string $plugin_id
   *   The id of the plugin being instantiated.
   * @param mixed[] $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface
   */
  public function createInstance($plugin_id, array $configuration = []);

}
