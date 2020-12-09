<?php

namespace Drupal\payment\Plugin\views\filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\plugin\Plugin\views\filter\PluginId;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a payment method filter.
 *
 * @ViewsFilter("payment_method")
 *
 * @deprecated Deprecated since >8.x-2.0-rc3. Use
 *   \Drupal\plugin\Plugin\views\filter\PluginId instead.
 */
class PaymentMethod extends PluginId implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $configuration['plugin_type_id'] = 'payment_method';
    return parent::create($container, $configuration, $plugin_id, $plugin_definition);
  }

}
