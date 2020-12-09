<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Method;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\Method\Basic;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Method\Basic
 *
 * @group Payment
 */
class BasicTest extends PaymentMethodBaseTestBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The payment status manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\Basic
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->pluginDefinition += array(
      'entity_id' => $this->randomMachineName(),
      'execute_status_id' => $this->randomMachineName(),
      'capture' => TRUE,
      'capture_status_id' => $this->randomMachineName(),
      'refund' => TRUE,
      'refund_status_id' => $this->randomMachineName(),
    );

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

    $this->sut = new Basic([], '', $this->pluginDefinition, $this->moduleHandler, $this->eventDispatcher, $this->token, $this->paymentStatusManager);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('payment.event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher),
      array('module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler),
      array('plugin.manager.payment.status', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentStatusManager),
      array('token', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->token),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = Basic::create($container, [], '', $this->pluginDefinition);
    $this->assertInstanceOf(Basic::class, $sut);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $this->assertIsArray($this->sut->defaultConfiguration());
  }

  /**
   * @covers ::getExecuteStatusId
   */
  public function testGetExecuteStatusId() {
    $this->assertSame($this->pluginDefinition['execute_status_id'], $this->sut->getExecuteStatusId());
  }

  /**
   * @covers ::getCaptureStatusId
   */
  public function testGetCaptureStatusId() {
    $this->assertSame($this->pluginDefinition['capture_status_id'], $this->sut->getCaptureStatusId());
  }

  /**
   * @covers ::getCapture
   */
  public function testGetCapture() {
    $this->assertSame($this->pluginDefinition['capture'], $this->sut->getCapture());
  }

  /**
   * @covers ::getRefundStatusId
   */
  public function testGetRefundStatusId() {
    $this->assertSame($this->pluginDefinition['refund_status_id'], $this->sut->getRefundStatusId());
  }

  /**
   * @covers ::getRefund
   */
  public function testGetRefund() {
    $this->assertSame($this->pluginDefinition['refund'], $this->sut->getRefund());
  }

  /**
   * @covers ::doExecutePayment
   */
  public function testDoExecutePayment() {
    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusManager->expects($this->once())
      ->method('createInstance')
      ->with($this->pluginDefinition['execute_status_id'])
      ->willReturn($payment_status);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->once())
      ->method('save');
    $payment->expects($this->once())
      ->method('setPaymentStatus')
      ->with($payment_status);

    $this->sut->setPayment($payment);

    $method = new \ReflectionMethod($this->sut, 'doExecutePayment');
    $method->setAccessible(TRUE);

    $method->invoke($this->sut, $payment);
  }

  /**
   * @covers ::doCapturePayment
   */
  public function testDoCapturePayment() {
    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusManager->expects($this->once())
      ->method('createInstance')
      ->with($this->pluginDefinition['capture_status_id'])
      ->willReturn($payment_status);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->once())
      ->method('save');
    $payment->expects($this->once())
      ->method('setPaymentStatus')
      ->with($payment_status);

    $this->sut->setPayment($payment);

    $method = new \ReflectionMethod($this->sut, 'doCapturePayment');
    $method->setAccessible(TRUE);

    $method->invoke($this->sut, $payment);
  }

  /**
   * @covers ::doCapturePaymentAccess
   *
   * @dataProvider providerDoCapturePaymentAccess
   */
  public function testDoCapturePaymentAccess($expected, $capture, $current_status_id, $capture_status_id) {
    $this->pluginDefinition['capture'] = $capture;
    $this->pluginDefinition['capture_status_id'] = $capture_status_id;

    $this->sut = new Basic([], '', $this->pluginDefinition, $this->moduleHandler, $this->eventDispatcher, $this->token, $this->paymentStatusManager);

    $capture_payment_status = $this->createMock(PaymentStatusInterface::class);
    $capture_payment_status->expects($this->any())
      ->method('getPluginId')
      ->willReturn($current_status_id);

    $capture_payment_status = $this->createMock(PaymentStatusInterface::class);
    $capture_payment_status->expects($this->any())
      ->method('getPluginId')
      ->willReturn($current_status_id);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->any())
      ->method('getPaymentStatus')
      ->willReturn($capture_payment_status);

    $this->sut->setPayment($payment);

    $account = $this->createMock(AccountInterface::class);

    $method = new \ReflectionMethod($this->sut, 'doCapturePaymentAccess');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invoke($this->sut, $account));
  }

  /**
   * Provides data to self::testDoCapturePaymentAccess().
   */
  public function providerDoCapturePaymentAccess() {
    $status_id_a = $this->randomMachineName();
    $status_id_b = $this->randomMachineName();
    $status_id_c = $this->randomMachineName();
    return array(
      array(TRUE, TRUE, $status_id_a, $status_id_b),
      array(TRUE, TRUE, $status_id_a, $status_id_c),
      array(FALSE, FALSE, $status_id_a, $status_id_b),
    );
  }

  /**
   * @covers ::doRefundPayment
   */
  public function testDoRefundPayment() {
    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusManager->expects($this->once())
      ->method('createInstance')
      ->with($this->pluginDefinition['refund_status_id'])
      ->willReturn($payment_status);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->once())
      ->method('save');
    $payment->expects($this->once())
      ->method('setPaymentStatus')
      ->with($payment_status);

    $this->sut->setPayment($payment);

    $method = new \ReflectionMethod($this->sut, 'doRefundPayment');
    $method->setAccessible(TRUE);

    $method->invoke($this->sut, $payment);
  }

  /**
   * @covers ::doRefundPaymentAccess
   *
   * @dataProvider providerDoRefundPaymentAccess
   */
  public function testDoRefundPaymentAccess($expected, $refund, $current_status_id, $refund_status_id) {
    $this->pluginDefinition['refund'] = $refund;
    $this->pluginDefinition['refund_status_id'] = $refund_status_id;

    $this->sut = new Basic([], '', $this->pluginDefinition, $this->moduleHandler, $this->eventDispatcher, $this->token, $this->paymentStatusManager);

    $payment_status = $this->createMock(PaymentStatusInterface::class);
    $payment_status->expects($this->any())
      ->method('getPluginId')
      ->willReturn($current_status_id);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->any())
      ->method('getPaymentStatus')
      ->willReturn($payment_status);

    $this->sut->setPayment($payment);

    $account = $this->createMock(AccountInterface::class);

    $method = new \ReflectionMethod($this->sut, 'doRefundPaymentAccess');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invoke($this->sut, $account));
  }

  /**
   * Provides data to self::testDoRefundPaymentAccess().
   */
  public function providerDoRefundPaymentAccess() {
    $status_id_a = $this->randomMachineName();
    $status_id_b = $this->randomMachineName();
    $status_id_c = $this->randomMachineName();
    return array(
      array(TRUE, TRUE, $status_id_a, $status_id_b),
      array(TRUE, TRUE, $status_id_a, $status_id_c),
      array(FALSE, FALSE, $status_id_a, $status_id_b),
    );
  }

  /**
   * @covers ::updatePaymentStatusAccess
   */
  public function testUpdatePaymentStatusAccess() {
    $account = $this->createMock(AccountInterface::class);

    $this->assertFalse($this->sut->updatePaymentStatusAccess($account)->isAllowed());
  }

  /**
   * @covers ::getSettablePaymentStatuses
   */
  public function testGetSettablePaymentStatuses() {
    $account = $this->createMock(AccountInterface::class);

    $payment = $this->createMock(PaymentInterface::class);

    $this->assertSame([], $this->sut->getSettablePaymentStatuses($account, $payment));
  }

  /**
   * @covers ::getSupportedCurrencies
   */
  public function testGetSupportedCurrencies() {
    $this->assertTrue($this->sut->getSupportedCurrencies());
  }

  /**
   * @covers ::getEntityId
   */
  public function testGetEntityId() {
    $this->assertSame($this->pluginDefinition['entity_id'], $this->sut->getEntityId());
  }

}
