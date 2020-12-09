<?php

namespace Drupal\Tests\payment\Unit\Plugin\views\field;

use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\payment\Plugin\views\field\PaymentMethodLabel;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\views\field\PaymentMethodLabel
 *
 * @group Payment
 */
class PaymentMethodLabelTest extends UnitTestCase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\views\field\PaymentMethodLabel
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentMethodManager = $this->createMock(PaymentMethodManagerInterface::class);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new PaymentMethodLabel($configuration, $plugin_id, $plugin_definition, $this->paymentMethodManager);
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
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('plugin.manager.payment.method', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodManager),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $sut = PaymentMethodLabel::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentMethodLabel::class, $sut);
  }

  /**
   * @covers ::render
   */
  public function testRender() {
    $plugin_id = $this->randomMachineName();
    $plugin_label = $this->randomMachineName();

    $plugin_definition = [
      'label' => $plugin_label,
    ];

    $this->paymentMethodManager->expects($this->atLeastOnce())
      ->method('getDefinition')
      ->with($plugin_id)
      ->willReturn($plugin_definition);

    $result_row = new ResultRow();
    $result_row->{$this->sut->field_alias} = $plugin_id;

    $this->assertSame($plugin_label, $this->sut->render($result_row));
  }

}
