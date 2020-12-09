<?php

namespace Drupal\payment\Annotations;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a payment method configuration plugin annotation.
 *
 * @Annotation
 */
class PaymentMethodConfiguration extends Plugin {

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
}
