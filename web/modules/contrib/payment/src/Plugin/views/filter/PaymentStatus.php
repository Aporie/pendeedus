<?php

namespace Drupal\payment\Plugin\views\filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\plugin\Plugin\views\filter\PluginId;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a payment status filter.
 *
 * @ViewsFilter("payment_status")
 *
 * @deprecated Deprecated since >8.x-2.0-rc3. Use
 *   \Drupal\plugin\Plugin\views\filter\PluginId instead.
 */
class PaymentStatus extends PluginId implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $configuration['plugin_type_id'] = 'payment_status';
    return parent::create($container, $configuration, $plugin_id, $plugin_definition);
  }

}
