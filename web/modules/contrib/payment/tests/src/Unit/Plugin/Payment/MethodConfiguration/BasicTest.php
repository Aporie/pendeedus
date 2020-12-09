<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\MethodConfiguration;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormState;
use Drupal\payment\Plugin\Payment\MethodConfiguration\Basic;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginType;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\MethodConfiguration\Basic
 *
 * @group Payment
 */
class BasicTest extends PaymentMethodConfigurationBaseTestBase {

  /**
   * The payment status manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusManager;

  /**
   * The payment status plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $paymentStatusType;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginSelectorManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\Basic
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

    $this->paymentStatusType = $this->prophesize(PluginTypeInterface::class);
    $this->paymentStatusType->getPluginManager()->willReturn($this->paymentStatusManager);

    $this->pluginSelectorManager = $this->createMock(PluginSelectorManagerInterface::class);

    $this->sut = new Basic([], '', $this->pluginDefinition, $this->stringTranslation, $this->moduleHandler, $this->pluginSelectorManager, $this->paymentStatusType->reveal());
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $plugin_type_manager = $this->prophesize(PluginTypeManagerInterface::class);
    $plugin_type_manager->getPluginType('payment_status')
      ->willReturn($this->paymentStatusType->reveal());

    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler),
      array('plugin.manager.plugin.plugin_selector', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->pluginSelectorManager),
      array('plugin.plugin_type_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $plugin_type_manager->reveal()),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = Basic::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(Basic::class, $sut);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = $this->sut->defaultConfiguration();
    $this->assertIsArray($configuration);
    foreach (array('brand_label', 'message_text', 'message_text_format', 'execute_status_id', 'capture_status_id') as $key) {
      $this->assertArrayHasKey($key, $configuration);
      $this->assertIsString( $configuration[$key]);
    }
    $this->assertArrayHasKey('capture', $configuration);
    $this->assertIsBool($configuration['capture']);
  }

  /**
   * @covers ::getExecuteStatusId
   * @covers ::setExecuteStatusId
   */
  public function testGetExecuteStatusId() {
    $status = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setExecuteStatusId($status));
    $this->assertSame($status, $this->sut->getExecuteStatusId());
  }

  /**
   * @covers ::getCaptureStatusId
   * @covers ::setCaptureStatusId
   */
  public function testGetCaptureStatusId() {
    $status = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setCaptureStatusId($status));
    $this->assertSame($status, $this->sut->getCaptureStatusId());
  }

  /**
   * @covers ::getCapture
   * @covers ::setCapture
   */
  public function testGetCapture() {
    $capture = TRUE;
    $this->assertSame($this->sut, $this->sut->setCapture($capture));
    $this->assertSame($capture, $this->sut->getCapture());
  }

  /**
   * @covers ::getRefundStatusId
   * @covers ::setRefundStatusId
   */
  public function testGetRefundStatusId() {
    $status = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setRefundStatusId($status));
    $this->assertSame($status, $this->sut->getRefundStatusId());
  }

  /**
   * @covers ::getRefund
   * @covers ::setRefund
   */
  public function testGetRefund() {
    $refund = TRUE;
    $this->assertSame($this->sut, $this->sut->setRefund($refund));
    $this->assertSame($refund, $this->sut->getRefund());
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $form = [];
    $form_state = new FormState();
    $elements = $this->sut->buildConfigurationForm($form, $form_state);
    $form['plugin_form']['#process'][] = array($this->sut, 'processBuildConfigurationForm');
    $this->assertArrayHasKey('message', $elements);
    $this->assertArrayHasKey('plugin_form', $elements);
    $this->assertSame(array(array($this->sut, 'processBuildConfigurationForm')), $elements['plugin_form']['#process']);
  }

  /**
   * @covers ::processBuildConfigurationForm
   * @covers ::getExecutePaymentStatusSelector
   * @covers ::getCapturePaymentStatusSelector
   * @covers ::getRefundPaymentStatusSelector
   * @covers ::getPaymentStatusSelector
   */
  public function testProcessBuildConfigurationForm() {
    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusManager->expects($this->at(0))
      ->method('createInstance')
      ->with('payment_pending')
      ->willReturn($payment_status);

    $this->paymentStatusManager->expects($this->at(1))
      ->method('createInstance')
      ->with('payment_success')
      ->willReturn($payment_status);

    $this->paymentStatusManager->expects($this->at(2))
      ->method('createInstance')
      ->with('payment_refunded')
      ->willReturn($payment_status);

    $payment_status_selector = $this->createMock(PluginSelectorInterface::class);

    $this->pluginSelectorManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->willReturn($payment_status_selector);

    $element = array(
      '#parents' => array('foo', 'bar'),
    );
    $form = [];
    $form_state = new FormState();

    $elements = $this->sut->processBuildConfigurationForm($element, $form_state, $form);
    $this->assertIsArray($elements);
    foreach (array('brand_label', 'execute', 'capture', 'refund') as $key) {
      $this->assertArrayHasKey($key, $elements);
      $this->assertIsArray($elements[$key]);
    }
  }

  /**
   * @covers ::submitConfigurationForm
   * @covers ::getExecutePaymentStatusSelector
   * @covers ::getCapturePaymentStatusSelector
   * @covers ::getRefundPaymentStatusSelector
   * @covers ::getPaymentStatusSelector
   */
  public function testSubmitConfigurationForm() {
    $brand_label = $this->randomMachineName();
    $message = $this->randomMachineName();
    $execute_status_id = $this->randomMachineName();
    $capture = TRUE;
    $capture_status_id = $this->randomMachineName();
    $refund = TRUE;
    $refund_status_id = $this->randomMachineName();

    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->willReturn($payment_status);

    $payment_status_selector = $this->createMock(PluginSelectorInterface::class);
    $payment_status_selector->expects($this->atLeastOnce())
      ->method('getSelectedPlugin')
      ->willReturn($payment_status);

    $this->pluginSelectorManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->willReturn($payment_status_selector);

    $form = array(
      'message' => array(
        '#parents' => array('foo', 'bar', 'message')
      ),
      'plugin_form' => array(
        'brand_label' => array(
          '#parents' => array('foo', 'bar', 'status')
        ),
        'execute' => [
          'execute_status' => [
            '#foo' => $this->randomMachineName(),
          ],
        ],
        'capture' => [
          'plugin_form' => [
            'capture_status' => [
              '#foo' => $this->randomMachineName(),
            ],
          ],
        ],
        'refund' => [
          'plugin_form' => [
            'refund_status' => [
              '#foo' => $this->randomMachineName(),
            ],
          ],
        ],
      ),
    );
    $form_state = new FormState();
    $form_state->setValues([
      'foo' => array(
        'bar' => array(
          'brand_label' => $brand_label,
          'message' => $message,
          'execute' => array(
            'execute_status_id' => $execute_status_id,
          ),
          'capture' => array(
            'capture' => $capture,
            'capture_status_id' => $capture_status_id,
          ),
          'refund' => array(
            'refund' => $refund,
            'refund_status_id' => $refund_status_id,
          ),
        ),
      ),
    ]);

    $this->sut->submitConfigurationForm($form, $form_state);

    $this->assertSame($brand_label, $this->sut->getBrandLabel());
    $this->assertSame($capture, $this->sut->getCapture());
    $this->assertSame($refund, $this->sut->getRefund());
  }

  /**
   * @covers ::getBrandLabel
   * @covers ::setBrandLabel
   */
  public function testGetBrandLabel() {
    $label = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setBrandLabel($label));
    $this->assertSame($label, $this->sut->getBrandLabel());
  }
}
