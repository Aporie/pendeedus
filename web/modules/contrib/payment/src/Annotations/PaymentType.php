<?php

namespace Drupal\payment\Annotations;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a payment type plugin annotation.
 *
 * @Annotation
 */
class PaymentType extends Plugin {

  /**
   * The name of the class that contains the plugin's global configuration form.
   *
   * @var string
   */
  public $configuration_form;

  /**
   * The translated human-readable plugin description (optional).
   *
   * @var string
   */
  public $description;

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
