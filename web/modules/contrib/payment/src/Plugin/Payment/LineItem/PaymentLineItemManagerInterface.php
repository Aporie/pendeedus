<?php

namespace Drupal\payment\Plugin\Payment\LineItem;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines a payment line item manager.
 */
interface PaymentLineItemManagerInterface extends PluginManagerInterface {

  /**
   * Creates a payment line item.
   *
   * @param string $plugin_id
   *   The id of the plugin being instantiated.
   * @param mixed[] $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface
   */
  public function createInstance($plugin_id, array $configuration = []);

}
