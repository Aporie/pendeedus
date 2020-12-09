<?php

namespace Drupal\Tests\payment\Unit\Plugin\Action;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Action\SetStatus;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Action\SetStatus
 *
 * @group Payment
 */
class SetStatusTest extends UnitTestCase {

  /**
   * The payment status manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusManager;

  /**
   * The payment status type.
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
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Action\SetStatus
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

    $this->pluginSelectorManager = $this->createMock(PluginSelectorManagerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->paymentStatusType = $this->prophesize(PluginTypeInterface::class);
    $this->paymentStatusType->getPluginManager()->willReturn($this->paymentStatusManager);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $this->sut = new SetStatus($configuration, $plugin_id, $plugin_definition, $this->stringTranslation, $this->pluginSelectorManager, $this->paymentStatusType->reveal());
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
    $sut = SetStatus::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(SetStatus::class, $sut);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = $this->sut->defaultConfiguration();
    $this->assertIsArray($configuration);
    $this->assertArrayHasKey('payment_status_plugin_id', $configuration);
  }

  /**
   * @covers ::buildConfigurationForm
   * @covers ::getPluginSelector
   */
  public function testBuildConfigurationForm() {
    $form = [];
    $form_state = new FormState();

    $plugin_selector_form = [
      '#foo' => $this->randomMachineName(),
    ];

    $plugin_selector = $this->createMock(PluginSelectorInterface::class);
    $plugin_selector->expects($this->once())
      ->method('buildSelectorForm')
      ->with([], $form_state)
      ->willReturn($plugin_selector_form);

    $this->pluginSelectorManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->willReturn($plugin_selector);

    $expected_form = [
      'payment_status_plugin_id' => $plugin_selector_form,
    ];

    $form = $this->sut->buildConfigurationForm($form, $form_state);
    $this->assertSame($expected_form, $form);
  }

  /**
   * @covers ::validateConfigurationForm
   * @covers ::getPluginSelector
   *
   * @depends testBuildConfigurationForm
   */
  public function testValidateConfigurationForm() {
    $form = [
      'payment_status_plugin_id' => [
        '#foo' => $this->randomMachineName(),
      ],
    ];
    $form_state = new FormState();

    $plugin_selector = $this->createMock(PluginSelectorInterface::class);
    $plugin_selector->expects($this->once())
      ->method('validateSelectorForm')
      ->with($form['payment_status_plugin_id'], $form_state);

    $this->pluginSelectorManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->willReturn($plugin_selector);

    $this->assertNull($this->sut->validateConfigurationForm($form, $form_state));
  }

  /**
   * @covers ::submitConfigurationForm
   * @covers ::getPluginSelector
   *
   * @depends testBuildConfigurationForm
   */
  public function testSubmitConfigurationForm() {
    $form = [
      'payment_status_plugin_id' => [
        '#foo' => $this->randomMachineName(),
      ],
    ];
    $form_state = new FormState();

    $plugin_id = $this->randomMachineName();

    $payment_status = $this->createMock(PaymentStatusInterface::class);
    $payment_status->expects($this->atLeastOnce())
      ->method('getPluginId')
      ->willReturn($plugin_id);


    $plugin_selector = $this->createMock(PluginSelectorInterface::class);
    $plugin_selector->expects($this->once())
      ->method('getSelectedPlugin')
      ->willReturn($payment_status);
    $plugin_selector->expects($this->once())
      ->method('submitSelectorForm')
      ->with($form['payment_status_plugin_id'], $form_state);

    $this->pluginSelectorManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->willReturn($plugin_selector);

    $this->sut->submitConfigurationForm($form, $form_state);
    $configuration = $this->sut->getConfiguration();
    $this->assertSame($plugin_id, $configuration['payment_status_plugin_id']);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $plugin_id = $this->randomMachineName();

    $status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusManager->expects($this->once())
      ->method('createInstance')
      ->with($plugin_id)
      ->willReturn($status);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->once())
      ->method('setPaymentStatus')
      ->with($status);

    $this->sut->setConfiguration(array(
      'payment_status_plugin_id' => $plugin_id,
    ));

    // Test execution without an argument to make sure it fails silently.
    $this->sut->execute();
    $this->sut->execute($payment);
  }

  /**
   * @covers ::access
   */
  public function testAccessWithPaymentAsObject() {
    $account = $this->createMock(AccountInterface::class);

    $access_result = new AccessResultAllowed();

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->atLeastOnce())
      ->method('access')
      ->with('update', $account, TRUE)
      ->willReturn($access_result);

    $this->assertSame($access_result, $this->sut->access($payment, $account, TRUE));
  }

  /**
   * @covers ::access
   */
  public function testAccessWithPaymentAsBoolean() {
    $account = $this->createMock(AccountInterface::class);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->atLeastOnce())
      ->method('access')
      ->with('update', $account)
      ->willReturn(TRUE);

    $this->assertTrue($this->sut->access($payment, $account));
  }

  /**
   * @covers ::access
   */
  public function testAccessWithoutPaymentAsObject() {
    $account = $this->createMock(AccountInterface::class);

    $access_result = $this->sut->access(NULL, $account, TRUE);
    $this->assertFalse($access_result->isAllowed());
  }

  /**
   * @covers ::access
   */
  public function testAccessWithoutPaymentAsBoolean() {
    $account = $this->createMock(AccountInterface::class);

    $access_result = $this->sut->access(NULL, $account);
    $this->assertFalse($access_result);
  }

}
