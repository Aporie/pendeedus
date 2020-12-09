<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Method;

use Drupal\payment\Plugin\Payment\Method\SupportedCurrency;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Method\SupportedCurrency
 *
 * @group Payment
 */
class SupportedCurrencyTest extends UnitTestCase {

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
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\SupportedCurrency
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->currencyCode = $this->randomMachineName();

    $this->maximumAmount = mt_rand();

    $this->minimumAmount = mt_rand();

    $this->sut = new SupportedCurrency($this->currencyCode, $this->minimumAmount, $this->maximumAmount);
  }

  /**
   * @covers ::getCurrencyCode
   */
  function testGetCurrencyCode() {
    $this->assertSame($this->currencyCode, $this->sut->getCurrencyCode());
  }

  /**
   * @covers ::getMinimumAmount
   */
  function testGetMinimumAmount() {
    $this->assertSame($this->minimumAmount, $this->sut->getMinimumAmount());
  }

  /**
   * @covers ::getMaximumAmount
   */
  function testGetMaximumAmount() {
    $this->assertSame($this->maximumAmount, $this->sut->getMaximumAmount());
  }

}
