<?php

namespace Drupal\payment;

use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface;

/**
 * Provides a line item collection.
 */
class LineItemCollection implements LineItemCollectionInterface {

  /**
   * The line items' ISO 4217 currency code.
   *
   * @var string|null $currency_code
   *   The currency code or NULL if the collection itself has no specific
   *   currency.
   */
  protected $currencyCode;

  /**
   * The line items.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface[]
   *   Keys are line item names.
   */
  protected $lineItems = [];

  /**
   * Constructs a new instance.
   *
   * @param string $currency_code
   * @param \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface[] $line_items
   */
  public function __construct($currency_code = NULL, array $line_items = []) {
    $this->currencyCode = $currency_code;
    $this->setLineItems($line_items);
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode($currency_code) {
    $this->currencyCode = $currency_code;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode() {
    return $this->currencyCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLineItems(array $line_items) {
    $this->lineItems = [];
    foreach ($line_items as $line_item) {
      $this->setLineItem($line_item);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLineItem(PaymentLineItemInterface $line_item) {
    $this->lineItems[$line_item->getName()] = $line_item;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetLineItem($name) {
    unset($this->lineItems[$name]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItems() {
    return $this->lineItems;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItem($name) {
    $line_items = $this->getLineItems();
    foreach ($line_items as $delta => $line_item) {
      if ($line_item->getName() == $name) {
        return $line_item;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemsByType($plugin_id) {
    $line_items = [];
    foreach ($this->getLineItems() as $name => $line_item) {
      if ($line_item->getPluginId() == $plugin_id) {
        $line_items[$name] = $line_item;
      }
    }

    return $line_items;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    $total = 0;
    foreach ($this->getLineItems() as $line_item) {
      $total = bcadd($total, $line_item->getTotalAmount(), 6);
    }

    return $total;
  }

}
