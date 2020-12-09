<?php

namespace Drupal\Tests\payment\Unit;

use Drupal\payment\Payment;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\payment\Payment
 *
 * @group Payment
 */
class PaymentTest extends UnitTestCase {

  /**
   * The host site's container.
   */
  protected $originalContainer;

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    \Drupal::unsetContainer();
  }

  /**
   * @covers ::lineItemManager
   */
  public function testLineItemManager() {
    $container = new Container();
    $line_item_manager = $this->createMock(PaymentLineItemManagerInterface::class);
    $container->set('plugin.manager.payment.line_item', $line_item_manager);
    \Drupal::setContainer($container);
    $this->assertSame($line_item_manager, Payment::lineItemManager());
  }

  /**
   * @covers ::methodManager
   */
  public function testMethodManager() {
    $container = new Container();
    $method_manager = $this->createMock(PaymentMethodManagerInterface::class);
    $container->set('plugin.manager.payment.method', $method_manager);
    \Drupal::setContainer($container);
    $this->assertSame($method_manager, Payment::methodManager());
  }

  /**
   * @covers ::methodConfigurationManager
   */
  public function testMethodConfigurationManager() {
    $container = new Container();
    $method_configuration_manager = $this->createMock(PaymentMethodConfigurationManagerInterface::class);
    $container->set('plugin.manager.payment.method_configuration', $method_configuration_manager);
    \Drupal::setContainer($container);
    $this->assertSame($method_configuration_manager, Payment::methodConfigurationManager());
  }

  /**
   * @covers ::statusManager
   */
  public function testStatusManager() {
    $container = new Container();
    $status_manager = $this->createMock(PaymentStatusManagerInterface::class);
    $container->set('plugin.manager.payment.status', $status_manager);
    \Drupal::setContainer($container);
    $this->assertSame($status_manager, Payment::statusManager());
  }

  /**
   * @covers ::typeManager
   */
  public function testTypeManager() {
    $container = new Container();
    $type_manager = $this->createMock(PaymentTypeManagerInterface::class);
    $container->set('plugin.manager.payment.type', $type_manager);
    \Drupal::setContainer($container);
    $this->assertSame($type_manager, Payment::typeManager());
  }

}
