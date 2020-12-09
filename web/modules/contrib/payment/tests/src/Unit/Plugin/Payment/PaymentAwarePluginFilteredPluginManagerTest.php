<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\PaymentAwareInterface;
use Drupal\payment\Plugin\Payment\PaymentAwarePluginManagerDecorator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\PaymentAwarePluginManagerDecorator
 *
 * @group Payment
 */
class PaymentAwarePluginFilteredPluginManagerTest extends UnitTestCase {

  /**
   * The payment to filter methods by.
   *
   * @var \Drupal\payment\Entity\PaymentInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The original plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\PaymentAwarePluginManagerDecorator
   */
  protected $sut;

  public function setUp(): void {
    $this->payment = $this->createMock(PaymentInterface::class);

    $this->pluginManager = $this->createMock(PluginManagerInterface::class);

    $this->sut = new PaymentAwarePluginManagerDecorator($this->payment, $this->pluginManager);
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstance() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_a = $this->createMock(PluginInspectionInterface::class);
    $plugin_id_b = $this->randomMachineName();
    $plugin_b = $this->createMock(PaymentAwarePluginFilteredPluginManagerUnitTestPaymentAwarePlugin::class);
    $plugin_b->expects($this->atLeastOnce())
      ->method('setPayment')
      ->with($this->payment);

    $map = [
      [$plugin_id_a, [], $plugin_a],
      [$plugin_id_b, [], $plugin_b],
    ];
    $this->pluginManager->expects($this->atLeast(count($map)))
      ->method('createInstance')
      ->willReturnMap($map);

    $this->assertSame($plugin_a, $this->sut->createInstance($plugin_id_a));
    $this->assertSame($plugin_b, $this->sut->createInstance($plugin_id_b));
  }

}

/**
 * Provides a payment-aware dummy plugin.
 */
abstract class PaymentAwarePluginFilteredPluginManagerUnitTestPaymentAwarePlugin implements PaymentAwareInterface, PluginInspectionInterface {
}
