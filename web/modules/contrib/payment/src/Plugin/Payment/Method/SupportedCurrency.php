<?php

namespace Drupal\payment\Plugin\Payment\Method;

/**
 * Provides a currency that is supported by a payment method.
 */
class SupportedCurrency implements SupportedCurrencyInterface {

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * The lowest supported amount.
   *
   * @var int|float
   */
  protected $minimumAmount;

  /**
   * The highest supported amount.
   *
   * @var int|float
   */
  protected $maximumAmount;

  /**
   * Constructs a new instance.
   *
   * @param string $currency_code
   *   The currency code.
   * @param int|float|null $minimum_amount
   *   The minimum amount or NULL if there is no minimum amount.
   * @param int|float|null @$maximum_amount
   *   The maximum amount or NULL if there is no maximum amount.
   */
  public function __construct($currency_code, $minimum_amount = NULL, $maximum_amount = NULL) {
    $this->currencyCode = $currency_code;
    $this->minimumAmount = $minimum_amount;
    $this->maximumAmount = $maximum_amount;
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
  public function getMinimumAmount() {
    return $this->minimumAmount;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaximumAmount() {
    return $this->maximumAmount;
  }

}
