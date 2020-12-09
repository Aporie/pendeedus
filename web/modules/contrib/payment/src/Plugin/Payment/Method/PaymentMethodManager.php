<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\PaymentMethodManager.
 */

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\payment\Annotations\PaymentMethod;
use Drupal\plugin\Plugin\PluginOperationsProviderPluginManagerTrait;

/**
 * Manages discovery and instantiation of payment method plugins.
 *
 * @see \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface
 */
class PaymentMethodManager extends DefaultPluginManager implements PaymentMethodManagerInterface, FallbackPluginManagerInterface {

  use PluginOperationsProviderPluginManagerTrait;

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
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class_resolver.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver) {
    parent::__construct('Plugin/Payment/Method', $namespaces, $module_handler, PaymentMethodInterface::class, PaymentMethod::class);
    $this->alterInfo('payment_method');
    $this->setCacheBackend($cache_backend, 'payment_method', ['payment_method']);
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'payment_unavailable';
  }

}
