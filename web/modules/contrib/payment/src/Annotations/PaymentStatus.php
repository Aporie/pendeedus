<?php

namespace Drupal\payment\Annotations;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a payment method plugin annotation.
 *
 * @Annotation
 */
class PaymentStatus extends Plugin {

  /**
   * The translated human-readable plugin name (optional).
   *
   * @var string
   */
  public $description = '';

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

  /**
   * The plugin ID of the parent status.
   *
   * @var string
   */
  public $parent_id;
}
