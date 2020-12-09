<?php

namespace Drupal\Tests\payment\Unit\Plugin\views\filter;

use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\payment\Plugin\views\filter\PaymentMethod;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\views\filter\PaymentMethod
 *
 * @group Payment
 */
class PaymentMethodTest extends UnitTestCase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodManager;

  /**
   * The payment method plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $paymentMethodPluginType;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\views\filter\PaymentMethod
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentMethodManager = $this->createMock(PaymentMethodManagerInterface::class);

    $this->paymentMethodPluginType = $this->prophesize(PluginTypeInterface::class);
    $this->paymentMethodPluginType->getPluginManager()->willReturn($this->paymentMethodManager);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new PaymentMethod($configuration, $plugin_id, $plugin_definition, $this->paymentMethodPluginType->reveal());
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $plugin_type_manager = $this->prophesize(PluginTypeManagerInterface::class);
    $plugin_type_manager->getPluginType('payment_method')->willReturn($this->paymentMethodPluginType);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('plugin.plugin_type_manager')->willReturn($plugin_type_manager->reveal());

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $sut = PaymentMethod::create($container->reveal(), $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentMethod::class, $sut);
  }

}
