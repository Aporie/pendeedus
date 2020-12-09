<?php

namespace Drupal\payment\Annotations;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a payment method plugin annotation.
 *
 * @Annotation
 */
class PaymentMethod extends Plugin {

  /**
   * Whether the plugin is active.
   *
   * Inactive plugins should not be used to initiate new payments with, but
   * appear in the administration user interface and can be used to continue
   * processing existing payments.
   *
   * @var bool
   */
  public $active = TRUE;

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The translated human-readable plugin name.
   *
   * @var string
   */
  public $label;

  /**
   * The name of the class that provides plugin operations.
   *
   * The class must implement \Drupal\plugin\PluginOperationsProviderInterface.
   *
   * @var string
   */
  public $operations_provider;
}
