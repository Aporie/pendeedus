<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface.
 */

namespace Drupal\payment\Plugin\Payment\LineItem;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\payment\PaymentAwareInterface;

/**
 * A payment line item.
 *
 * Plugins can additionally implement the following interfaces:
 * - \Drupal\Component\Plugin\ConfigurableInterface
 *   Required if the plugin has any internal configuration, so it can be
 *   exported for recreation of the plugin at a later time.
 */
interface PaymentLineItemInterface extends PluginInspectionInterface, PluginFormInterface, PaymentAwareInterface {

  /**
   * Gets the amount.
   *
   * @return float|int|string
   *   A numeric value.
   */
  public function getAmount();

  /**
   * Return this line item's total amount.
   *
   * @return float
   */
  function getTotalAmount();

  /**
   * Sets the machine name.
   *
   * @param string $name
   *
   * @return static
   */
  public function setName($name);

  /**
   * Gets the machine name.
   *
   * @return string
   */
  public function getName();

  /**
   * Gets the line item description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Gets the currency_code.
   *
   * @return string
   */
  public function getCurrencyCode();

  /**
   * Sets the quantity.
   *
   * @param int|float $quantity
   *
   * @return static
   */
  public function setQuantity($quantity);

  /**
   * Gets the quantity.
   *
   * @return int|float
   */
  public function getQuantity();

}
