<?php

namespace Drupal\Tests\payment\Unit;

use Drupal\payment\LineItemCollection;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\LineItemCollection
 *
 * @group Payment
 */
class LineItemCollectionTest extends UnitTestCase {

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
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
   *   Keys are line item names.
   */
  protected $lineItems = [];

  /**
   * The subject under test.
   *
   * @var \Drupal\payment\LineItemCollection
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->currencyCode = $this->randomMachineName();

    $line_item_name_a = $this->randomMachineName();
    $line_item_a = $this->createMock(PaymentLineItemInterface::class);
    $line_item_a->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($line_item_name_a);
    $line_item_name_b = $this->randomMachineName();
    $line_item_b = $this->createMock(PaymentLineItemInterface::class);
    $line_item_b->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($line_item_name_b);

    $this->lineItems = [
      $line_item_name_a => $line_item_a,
      $line_item_name_b => $line_item_b,
    ];

    $this->sut = new LineItemCollection($this->currencyCode, $this->lineItems);
  }

  /**
   * @covers ::__construct
   * @covers ::getLineItem
   * @covers ::getLineItems
   */
  public function testConstruct() {
    $this->sut = new LineItemCollection($this->currencyCode, $this->lineItems);

    // Test that all line items can be retrieved individually after they have
    // been injected through the constructor.
    foreach ($this->lineItems as $name => $line_item) {
      $this->assertSame($line_item, $this->sut->getLineItem($name));
    }

    // Test that all line items can be retrieved after they have been injected
    // through the constructor.
    $this->assertSame($this->lineItems, $this->sut->getLineItems());

    // Test that the currency code can be retrieved after it has been injected
    // through the constructor.
    $this->assertSame($this->currencyCode, $this->sut->getCurrencyCode());
  }

  /**
   * @covers ::getCurrencyCode
   * @covers ::setCurrencyCode
   */
  public function testGetCurrencyCode() {
    $currency_code = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setCurrencyCode($currency_code));
    $this->assertSame($currency_code, $this->sut->getCurrencyCode());
  }

  /**
   * @covers ::setLineItem
   * @covers ::getLineItem
   * @covers ::getLineItems
   */
  public function testSetLineItem() {
    $line_item_name = $this->randomMachineName();
    $line_item = $this->createMock(PaymentLineItemInterface::class);
    $line_item->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($line_item_name);

    $this->assertSame($this->sut, $this->sut->setLineItem($line_item));
    $this->assertSame($line_item, $this->sut->getLineItem($line_item_name));
    $expected = $this->lineItems + [
      $line_item_name => $line_item,
    ];
    $this->assertSame($expected, $this->sut->getLineItems());
  }

  /**
   * @covers ::setLineItems
   * @covers ::getLineItem
   * @covers ::getLineItems
   */
  public function testSetLineItems() {
    $line_item_name_a = $this->randomMachineName();
    $line_item_a = $this->createMock(PaymentLineItemInterface::class);
    $line_item_a->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($line_item_name_a);
    $line_item_name_b = $this->randomMachineName();
    $line_item_b = $this->createMock(PaymentLineItemInterface::class);
    $line_item_b->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($line_item_name_b);

    $line_items = [
      $line_item_name_a => $line_item_a,
      $line_item_name_b => $line_item_b,
    ];

    $this->assertSame($this->sut, $this->sut->setLineItems($line_items));
    $this->assertSame($line_item_a, $this->sut->getLineItem($line_item_name_a));
    $this->assertSame($line_item_b, $this->sut->getLineItem($line_item_name_b));
    $expected = [
      $line_item_name_a => $line_item_a,
      $line_item_name_b => $line_item_b,
    ];
    $this->assertSame($expected, $this->sut->getLineItems());
  }

  /**
   * @covers ::unsetLineItem
   * @covers ::getLineItem
   * @covers ::getLineItems
   */
  public function testUnsetLineItems() {
    list($line_item_name_a, $line_item_name_b) = array_keys($this->lineItems);
    $this->assertSame($this->sut, $this->sut->unsetLineItem($line_item_name_a));
    $this->assertNull($this->sut->getLineItem($line_item_name_a));
    $this->assertSame($this->lineItems[$line_item_name_b], $this->sut->getLineItem($line_item_name_b));
    $expected = [
      $line_item_name_b => $this->lineItems[$line_item_name_b],
    ];
    $this->assertSame($expected, $this->sut->getLineItems());
  }

  /**
   * @covers ::getLineItemsByType
   */
  public function testGetLineItemsByType() {
    $line_item_type = $this->randomMachineName();

    /** @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface|\PHPUnit\Framework\MockObject\MockObject $line_item_a */
    $line_item_a = reset($this->lineItems);
    $line_item_name_a = key($this->lineItems);
    $line_item_a->expects($this->atLeastOnce())
      ->method('getPluginId')
      ->willReturn($line_item_type);
    $line_item_b = end($this->lineItems);
    $line_item_b->expects($this->atLeastOnce())
      ->method('getPluginId')
      ->willReturn($this->randomMachineName());

    $expected = [
      $line_item_name_a => $line_item_a,
    ];
    $this->assertSame($expected, $this->sut->getLineItemsByType($line_item_type));
  }

  /**
   * @covers ::getAmount
   */
  public function testGetAmount() {
    $line_item_amount_a = mt_rand();
    $line_item_amount_b = mt_rand();

    /** @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface|\PHPUnit\Framework\MockObject\MockObject $line_item_a */
    /** @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface|\PHPUnit\Framework\MockObject\MockObject $line_item_b */
    list($line_item_a, $line_item_b) = array_values($this->lineItems);

    $line_item_a->expects($this->atLeastOnce())
      ->method('getTotalAmount')
      ->willReturn($line_item_amount_a);
    $line_item_b->expects($this->atLeastOnce())
      ->method('getTotalAmount')
      ->willReturn($line_item_amount_b);

    $this->assertSame(bcadd($line_item_amount_a, $line_item_amount_b, 6), $this->sut->getAmount());
  }

}
