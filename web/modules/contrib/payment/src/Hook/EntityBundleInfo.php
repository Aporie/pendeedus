<?php

namespace Drupal\payment\Hook;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Implements hook_entity_bundle_info().
 *
 * @see payment_entity_bundle_info()
 */
class EntityBundleInfo {

  /**
   * The payment method configuration manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The payment type manager
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $paymentTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $payment_type_manager
   * @param \Drupal\Component\Plugin\PluginManagerInterface $payment_method_configuration_manager
   */
  public function __construct(PluginManagerInterface $payment_type_manager, PluginManagerInterface $payment_method_configuration_manager) {
    $this->paymentMethodConfigurationManager = $payment_method_configuration_manager;
    $this->paymentTypeManager = $payment_type_manager;
  }

  /**
   * Invokes the implementation.
   */
  public function invoke() {
    $bundles = [];

    foreach ($this->paymentTypeManager->getDefinitions() as $plugin_id => $definition) {
      $bundles['payment'][$plugin_id] = array(
        'label' => $definition['label'],
      );
    }
    foreach ($this->paymentMethodConfigurationManager->getDefinitions() as $plugin_id => $definition) {
      $bundles['payment_method_configuration'][$plugin_id] = array(
        'label' => $definition['label'],
      );
    }

    return $bundles;
  }

}
