<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManager.
 */

namespace Drupal\payment\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\payment\Annotations\PaymentMethodConfiguration;

/**
 * Manages discovery and instantiation of payment method configuration plugins.
 *
 * @see \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationInterface
 */
class PaymentMethodConfigurationManager extends DefaultPluginManager implements PaymentMethodConfigurationManagerInterface, FallbackPluginManagerInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Payment/MethodConfiguration', $namespaces, $module_handler, PaymentMethodConfigurationInterface::class, PaymentMethodConfiguration::class);
    $this->alterInfo('payment_method_configuration');
    $this->setCacheBackend($cache_backend, 'payment_method_configuration', ['payment_method_configuration']);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'payment_unavailable';
  }

}
