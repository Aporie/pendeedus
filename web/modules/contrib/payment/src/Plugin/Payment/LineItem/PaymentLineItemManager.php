<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManager.
 */

namespace Drupal\payment\Plugin\Payment\LineItem;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\payment\Annotations\PaymentLineItem;

/**
 * Manages discovery and instantiation of payment line item plugins.
 *
 * @see \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface
 */
class PaymentLineItemManager extends DefaultPluginManager implements PaymentLineItemManagerInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Traversable $namespaces
   *   The namespaces in which to look for plugins.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Payment/LineItem', $namespaces, $module_handler, PaymentLineItemInterface::class, PaymentLineItem::class);
    $this->alterInfo('payment_line_item');
    $this->setCacheBackend($cache_backend, 'payment_line_item', ['payment_line_item']);
  }

}
