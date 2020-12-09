<?php

namespace Drupal\Tests\payment\Unit\Plugin\views\field;

use Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface;
use Drupal\payment\Plugin\views\field\PaymentTypeLabel;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\views\field\PaymentTypeLabel
 *
 * @group Payment
 */
class PaymentTypeLabelTest extends UnitTestCase {

  /**
   * The line item manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentTypeManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\views\field\PaymentTypeLabel
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentTypeManager = $this->createMock(PaymentTypeManagerInterface::class);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new PaymentTypeLabel($configuration, $plugin_id, $plugin_definition, $this->paymentTypeManager);
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
      array('plugin.manager.payment.type', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentTypeManager),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $sut = PaymentTypeLabel::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentTypeLabel::class, $sut);
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

    $this->paymentTypeManager->expects($this->atLeastOnce())
      ->method('getDefinition')
      ->with($plugin_id)
      ->willReturn($plugin_definition);

    $result_row = new ResultRow();
    $result_row->{$this->sut->field_alias} = $plugin_id;

    $this->assertSame($plugin_label, $this->sut->render($result_row));
  }

}
