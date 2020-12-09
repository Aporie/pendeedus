<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Method;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\OperationResultInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Method\SupportedCurrency;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Method\PaymentMethodBase
 *
 * @group Payment
 */
class PaymentMethodBaseTest extends PaymentMethodBaseTestBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->pluginDefinition['label'] = $this->randomMachineName();

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->sut = $this->getMockBuilder(PaymentMethodBase::class)
      ->setConstructorArgs([[], '', $this->pluginDefinition, $this->moduleHandler, $this->eventDispatcher, $this->token, $this->paymentStatusManager])
      ->getMockForAbstractClass();
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['payment.event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher],
      ['module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler],
      ['plugin.manager.payment.status', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentStatusManager],
      ['token', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->token],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodBase $class_name */
    $class_name = get_class($this->sut);
    $form = $class_name::create($container, [], '', $this->pluginDefinition);
    $this->assertInstanceOf($class_name, $form);
  }

  /**
   * Creates a mock payment.
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  protected function getMockPayment() {
    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->any())
      ->method('getCacheContexts')
      ->willReturn([]);
    $payment->expects($this->any())
      ->method('getCacheTags')
      ->willReturn([]);

    return $payment;
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $this->assertSame([], $this->sut->defaultConfiguration());
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->assertSame([], $this->sut->calculateDependencies());
  }

  /**
   * @covers ::doExecutePaymentAccess
   */
  public function testDoExecutePaymentAccess() {
    $method = new \ReflectionMethod($this->sut, 'doExecutePaymentAccess');
    $method->setAccessible(TRUE);

    $account = $this->createMock(AccountInterface::class);

    $access = $method->invoke($this->sut, $account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertTrue($access->isAllowed());
  }

  /**
   * @covers ::setConfiguration
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $configuration = [
      $this->randomMachineName() => mt_rand(),
    ];
    $this->assertNull($this->sut->setConfiguration($configuration));
    $this->assertSame($configuration, $this->sut->getConfiguration());
  }

  /**
   * @covers ::setPayment
   * @covers ::getPayment
   */
  public function testGetPayment() {
    $payment = $this->getMockPayment();
    $this->assertSame($this->sut, $this->sut->setPayment($payment));
    $this->assertSame($payment, $this->sut->getPayment());
  }

  /**
   * @covers ::getMessageText
   */
  public function testGetMessageText() {
    $this->assertSame($this->pluginDefinition['message_text'], $this->sut->getMessageText());
  }

  /**
   * @covers ::getMessageTextFormat
   */
  public function testGetMessageTextFormat() {
    $this->assertSame($this->pluginDefinition['message_text_format'], $this->sut->getMessageTextFormat());
  }

  /**
   * @covers ::buildConfigurationForm
   *
   * @dataProvider providerTestBuildConfigurationForm
   */
  public function testBuildConfigurationForm($filter_exists) {
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('moduleExists')
      ->with('filter')
      ->willReturn($filter_exists);

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $payment = $this->getMockPayment();
    $elements = $this->sut->buildConfigurationForm($form, $form_state, $payment);
    $this->assertIsArray($elements);
    $this->assertArrayHasKey('message', $elements);
    $this->assertIsArray($elements['message']);
  }

  /**
   * Provides data to self::testBuildConfigurationForm().
   */
  public function providerTestBuildConfigurationForm() {
    return [
      [TRUE],
      [FALSE],
    ];
  }

  /**
   * @covers ::executePayment
   */
  public function testExecutePaymentWithoutPayment() {
    $this->expectException(\Exception::class);
    $this->sut->executePayment();
  }

  /**
   * @covers ::executePayment
   * @covers ::doExecutePayment
   */
  public function testExecutePayment() {
    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusManager->expects($this->atLeastOnce())
      ->method('createInstance')
      ->with('payment_pending')
      ->willReturn($payment_status);

    $payment = $this->getMockPayment();

    $this->eventDispatcher->expects($this->once())
      ->method('preExecutePayment')
      ->with($payment);

    $this->sut->setPayment($payment);

    $this->sut->executePayment();
  }

  /**
   * @covers ::executePaymentAccess
   */
  public function testExecutePaymentAccessWithoutPayment() {
    $this->expectException(\Exception::class);
    $account = $this->createMock(AccountInterface::class);

    $this->sut->executePaymentAccess($account);
  }

  /**
   * @covers ::executePaymentAccess
   *
   * @dataProvider providerTestExecutePaymentAccess
   */
  public function testExecutePaymentAccess($expected, $active, AccessResultInterface $currency_supported, AccessResultInterface $events_access_result, AccessResultInterface $do) {
    $payment = $this->getMockPayment();

    $account = $this->createMock(AccountInterface::class);

    $this->pluginDefinition['active'] = $active;
    /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodBase|\PHPUnit\Framework\MockObject\MockObject $payment_method */
    $payment_method = $this->getMockBuilder(PaymentMethodBase::class)
      ->setConstructorArgs([[], '', $this->pluginDefinition, $this->moduleHandler, $this->eventDispatcher, $this->token, $this->paymentStatusManager])
      ->setMethods(['executePaymentAccessCurrency', 'executePaymentAccessEvent', 'doExecutePaymentAccess'])
      ->getMockForAbstractClass();
    $payment_method->expects($this->any())
      ->method('executePaymentAccessCurrency')
      ->with($account)
      ->willReturn($currency_supported);
    $payment_method->expects($this->any())
      ->method('doExecutePaymentAccess')
      ->with($account)
      ->willReturn($do);
    $payment_method->setPayment($payment);

    $this->eventDispatcher->expects($this->any())
      ->method('executePaymentAccess')
      ->with($payment, $payment_method, $account)
      ->willReturn($events_access_result);

    $access = $payment_method->executePaymentAccess($account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertSame($expected, $access->isAllowed());
  }

  /**
   * Provides data to self::testExecutePaymentAccess().
   */
  public function providerTestExecutePaymentAccess() {
    return [
      [TRUE, TRUE, AccessResult::allowed(), AccessResult::allowed(), AccessResult::allowed()],
      [FALSE, TRUE, AccessResult::allowed(), AccessResult::neutral(), AccessResult::allowed()],
      [FALSE, FALSE, AccessResult::allowed(), AccessResult::allowed(), AccessResult::allowed()],
      [FALSE, FALSE, AccessResult::allowed(), AccessResult::neutral(), AccessResult::allowed()],
      [FALSE, TRUE, AccessResult::forbidden(), AccessResult::allowed(), AccessResult::allowed()],
      [FALSE, TRUE, AccessResult::forbidden(), AccessResult::neutral(), AccessResult::allowed()],
      [FALSE, TRUE, AccessResult::allowed(), AccessResult::forbidden(), AccessResult::allowed()],
      [FALSE, TRUE, AccessResult::allowed(), AccessResult::allowed(), AccessResult::forbidden()],
      [FALSE, TRUE, AccessResult::allowed(), AccessResult::neutral(), AccessResult::forbidden()],
    ];
  }

  /**
   * @covers ::capturePayment
   * @covers ::doCapturePayment
   */
  public function testCapturePayment() {
    $this->expectException(\Exception::class);
    $payment = $this->getMockPayment();

    $this->sut->setPayment($payment);

    $result = $this->sut->capturePayment();

    $this->assertInstanceOf(OperationResultInterface::class, $result);
  }

  /**
   * @covers ::capturePayment
   */
  public function testCapturePaymentWithoutPayment() {
    $this->expectException(\Exception::class);
    $this->sut->capturePayment();
  }

  /**
   * @covers ::capturePaymentAccess
   * @covers ::doCapturePaymentAccess
   */
  public function testCapturePaymentAccess() {
    $payment = $this->getMockPayment();

    $account = $this->createMock(AccountInterface::class);

    $this->sut->setPayment($payment);

    $access = $this->sut->capturePaymentAccess($account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertFalse($access->isAllowed());
  }

  /**
   * @covers ::capturePaymentAccess
   */
  public function testCapturePaymentAccessWithoutPayment() {
    $this->expectException(\Exception::class);
    $account = $this->createMock(AccountInterface::class);

    $this->sut->capturePaymentAccess($account);
  }

  /**
   * @covers ::refundPayment
   * @covers ::doRefundPayment
   */
  public function testRefundPayment() {
    $this->expectException(\Exception::class);
    $payment = $this->getMockPayment();

    $this->sut->setPayment($payment);

    $result = $this->sut->refundPayment();

    $this->assertInstanceOf(OperationResultInterface::class, $result);
  }

  /**
   * @covers ::refundPayment
   */
  public function testRefundPaymentWithoutPayment() {
    $this->expectException(\Exception::class);
    $this->sut->refundPayment();
  }

  /**
   * @covers ::refundPaymentAccess
   * @covers ::doRefundPaymentAccess
   */
  public function testRefundPaymentAccess() {
    $payment = $this->getMockPayment();

    $account = $this->createMock(AccountInterface::class);

    /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodBase|\PHPUnit\Framework\MockObject\MockObject $payment_method */
    $this->sut->setPayment($payment);

    $access = $this->sut->refundPaymentAccess($account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertFalse($access->isAllowed());
  }

  /**
   * @covers ::refundPaymentAccess
   */
  public function testRefundPaymentAccessWithoutPayment() {
    $this->expectException(\Exception::class);
    $account = $this->createMock(AccountInterface::class);

    $this->sut->refundPaymentAccess($account);
  }

  /**
   * Provides data to self::testExecutePaymentAccessEvent().
   */
  public function providerTestExecutePaymentAccessEvent() {
    return [
      // Access allowed.
      [TRUE, new AccessResultAllowed()],
      // Access forbidden.
      [FALSE, new AccessResultForbidden()],
      // Access neutral.
      [FALSE, new AccessResultNeutral()],
    ];
  }

  /**
   * @covers ::executePaymentAccessCurrency
   *
   * @dataProvider providerTestExecutePaymentAccessCurrency
   */
  public function testExecutePaymentAccessCurrency($expected, $supported_currencies, $payment_currency_code, $payment_amount) {
    $payment = $this->getMockPayment();
    $payment->expects($this->atLeastOnce())
      ->method('getAmount')
      ->willReturn($payment_amount);
    $payment->expects($this->atLeastOnce())
      ->method('getCurrencyCode')
      ->willReturn($payment_currency_code);

    $this->sut->setPayment($payment);
    $this->sut->expects($this->atLeastOnce())
      ->method('getSupportedCurrencies')
      ->willReturn($supported_currencies);

    $account = $this->createMock(AccountInterface::class);

    $method = new \ReflectionMethod($this->sut, 'executePaymentAccessCurrency');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invoke($this->sut, $account)->isAllowed());
  }

  /**
   * Provides data to self::testExecutePaymentAccessCurrency().
   */
  public function providerTestExecutePaymentAccessCurrency() {
    return [
      // All currencies are allowed.
      [TRUE, TRUE, $this->randomMachineName(), mt_rand()],
      // The payment currency is allowed. No amount limitations.
      [TRUE, [new SupportedCurrency('ABC')], 'ABC', mt_rand()],
      // The payment currency is allowed with amount limitations.
      [TRUE, [new SupportedCurrency('ABC', 1, 3)], 'ABC', 2],
      // The payment currency is not allowed.
      [FALSE, [new SupportedCurrency('ABC')], 'XXX', mt_rand()],
      // The payment currency is not allowed because of amount limitations.
      [FALSE, [new SupportedCurrency('ABC', 2)], 'ABC', 1],
      [FALSE, [new SupportedCurrency('ABC', NULL, 1)], 'ABC', 2],
    ];
  }

}
