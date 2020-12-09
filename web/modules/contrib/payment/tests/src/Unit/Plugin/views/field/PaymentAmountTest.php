<?php

namespace Drupal\Tests\payment\Unit\Plugin\views\field;

use Drupal\currency\Entity\CurrencyInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\views\field\PaymentAmount;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\views\field\PaymentAmount
 *
 * @group Payment
 */
class PaymentAmountTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\views\field\PaymentAmount
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new PaymentAmount($configuration, $plugin_id, $plugin_definition);
    $options = [
      'relationship' => 'none',
    ];
    $view_executable = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();
    $display = $this->getMockBuilder(DisplayPluginBase::class)
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $this->sut->init($view_executable, $display, $options);
  }

  /**
   * @covers ::render
   */
  public function testRender() {
    $amount = mt_rand();

    $formatted_amount = 'FooBar ' . mt_rand();

    $currency = $this->createMock(CurrencyInterface::class);
    $currency->expects($this->atLeastOnce())
      ->method('formatAmount')
      ->with($amount)
      ->willReturn($formatted_amount);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->atLeastOnce())
      ->method('getAmount')
      ->willReturn($amount);
    $payment->expects($this->atLeastOnce())
      ->method('getCurrency')
      ->willReturn($currency);

    $result_row = new ResultRow();
    $result_row->_entity = $payment;

    $this->assertSame($formatted_amount, $this->sut->render($result_row));
  }

}
