<?php

namespace Drupal\Tests\payment\Unit\Plugin\views\filter;

use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\payment\Plugin\views\filter\PaymentStatus;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\views\filter\PaymentStatus
 *
 * @group Payment
 */
class PaymentStatusTest extends UnitTestCase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusManager;

  /**
   * The payment status plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $paymentStatusPluginType;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\views\filter\PaymentStatus
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

    $this->paymentStatusPluginType= $this->prophesize(PluginTypeInterface::class);
    $this->paymentStatusPluginType->getPluginManager()->willReturn($this->paymentStatusManager);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new PaymentStatus($configuration, $plugin_id, $plugin_definition, $this->paymentStatusPluginType->reveal());
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $plugin_type_manager = $this->prophesize(PluginTypeManagerInterface::class);
    $plugin_type_manager->getPluginType('payment_status')->willReturn($this->paymentStatusPluginType);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('plugin.plugin_type_manager')->willReturn($plugin_type_manager->reveal());

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $sut = PaymentStatus::create($container->reveal(), $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentStatus::class, $sut);
  }

}
