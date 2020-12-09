<?php

namespace Drupal\payment\Annotations;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a payment line item plugin annotation.
 *
 * @Annotation
 */
class PaymentLineItem extends Plugin {

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
