<?php

namespace Drupal\payment\Plugin\Payment\Method;

/**
 * Defines a currency that is supported by a payment method.
 */
interface SupportedCurrencyInterface {

  /**
   * Gets the currency code.
   *
   * @return string
   */
  public function getCurrencyCode();

  /**
   * Gets the lowest supported amount.
   *
   * @return int|float|null
   *   The amount or NULL if there is no minimum amount.
   */
  public function getMinimumAmount();

  /**
   * Gets the highest supported amount.
   *
   * @return int|float|null
   *   The amount or NULL if there is no maximum amount.
   */
  public function getMaximumAmount();

}
