<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\LineItem;

use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemBase
 *
 * @group Payment
 */
class PaymentLineItemBaseTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\Basic|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = $this->getMockBuilder(PaymentLineItemBase::class)
      ->setConstructorArgs(array($configuration, $plugin_id, $plugin_definition))
      ->getMockForAbstractClass();
  }

  /**
   * @covers ::setConfiguration
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $configuration = array(
      $this->randomMachineName() => mt_rand(),
    ) + $this->sut->defaultConfiguration();
    $return = $this->sut->setConfiguration($configuration);
    $this->assertNull($return);
    $this->assertSame($configuration, $this->sut->getConfiguration());
  }

  /**
   * @covers ::setQuantity
   * @covers ::getQuantity
   */
  public function testGetQuantity() {
    $quantity = 7.7;
    $this->assertSame($this->sut, $this->sut->setQuantity($quantity));
    $this->assertSame($quantity, $this->sut->getQuantity());
  }

  /**
   * @covers ::getTotalAmount
   */
  public function testGetTotalAmount() {
    $amount= 7;
    $quantity = 7;
    $total_amount = bcmul($amount, $quantity, 6);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    /** @var \Drupal\payment\Plugin\Payment\LineItem\Basic|\PHPUnit\Framework\MockObject\MockObject $line_item */
    $line_item = $this->getMockBuilder(PaymentLineItemBase::class)
      ->setMethods(array('formElements', 'getAmount', 'getConfigurationFromFormValues', 'getCurrencyCode', 'getDescription', 'getQuantity'))
      ->setConstructorArgs(array($configuration, $plugin_id, $plugin_definition))
      ->getMock();
    $line_item->expects($this->once())
      ->method('getAmount')
      ->willReturn($amount);
    $line_item->expects($this->once())
      ->method('getQuantity')
      ->willReturn($quantity);

    $this->assertSame($total_amount, $line_item->getTotalAmount());
  }

  /**
   * @covers ::setName
   * @covers ::getName
   */
  public function testGetName() {
    $name = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setName($name));
    $this->assertSame($name, $this->sut->getName());
  }

  /**
   * @covers ::setPayment
   * @covers ::getPayment
   */
  public function testGetPayment() {
    $payment = $this->createMock(PaymentInterface::class);
    $this->assertSame($this->sut, $this->sut->setPayment($payment));
    $this->assertSame($payment, $this->sut->getPayment());
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->assertSame([], $this->sut->calculateDependencies());
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $default_configuration = array(
      'name' => NULL,
      'quantity' => 1,
    );

    $this->assertSame($default_configuration, $this->sut->defaultConfiguration());
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $this->assertSame([], $this->sut->buildConfigurationForm($form, $form_state));
  }

}
