<?php

namespace Drupal\payment;

use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface;

/**
 * Defines a line item collection.
 */
interface LineItemCollectionInterface {

  /**
   * Sets the line items' ISO 4217 currency code.
   *
   * @param string $currency_code
   *
   * @return static
   */
  public function setCurrencyCode($currency_code);

  /**
   * Gets the line items' ISO 4217 currency code.
   *
   * @return string
   */
  public function getCurrencyCode();

  /**
   * Sets line items.
   *
   * @param \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface[] $line_items
   *
   * @return static
   */
  public function setLineItems(array $line_items);

  /**
   * Sets a line item.
   *
   * @param \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface $line_item
   *
   * @return static
   */
  public function setLineItem(PaymentLineItemInterface $line_item);

  /**
   * Unsets a line item.
   *
   * @param string $name
   *   The line item's name.
   *
   * @return static
   */
  public function unsetLineItem($name);

  /**
   * Gets all line items.
   *
   * @return \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface[]
   */
  public function getLineItems();

  /**
   * Gets a line item.
   *
   * @param string $name
   *   The line item's machine name.
   *
   * @return \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface
   */
  public function getLineItem($name);

  /**
   * Gets line items by plugin type.
   *
   * @param string $plugin_id
   *   The line item plugin's ID.
   *
   * @return \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface[]
   */
  public function getLineItemsByType($plugin_id);

  /**
   * Gets the line items' total amount.
   *
   * @return float|int|string
   *   A numeric value.
   */
  public function getAmount();
}
