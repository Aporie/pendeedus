<?php

namespace Drupal\Tests\payment\Unit\Entity\Payment;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\Payment\PaymentAccessControlHandler;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\OperationResultInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodCapturePaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodRefundPaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodUpdatePaymentStatusInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Entity\Payment\PaymentAccessControlHandler
 *
 * @group Payment
 */
class PaymentAccessControlHandlerTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Entity\Payment\PaymentAccessControlHandler
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $cache_context_manager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cache_context_manager->expects($this->any())
      ->method('assertValidTokens')
      ->willReturn(TRUE);

    $container = new Container();
    $container->set('cache_contexts_manager', $cache_context_manager);
    \Drupal::setContainer($container);

    $entity_type = $this->createMock(EntityTypeInterface::class);

    $this->sut = new PaymentAccessControlHandler($entity_type);
  }

  /**
   * Gets a mock payment.
   *
   * @return \Drupal\payment\Entity\PaymentInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getMockPayment() {
    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->any())
      ->method('getCacheContexts')
      ->willReturn([]);

    return $payment;
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestCheckAccessCapture
   */
  public function testCheckAccessCapture($expected, $payment_method_interface, $payment_method_capture_access, $has_permissions) {
    $operation = 'capture';

    $account = $this->createMock(AccountInterface::class);
    $map = array(
      array('payment.payment.capture.any', $has_permissions),
      array('payment.payment.capture.own', $has_permissions),
    );
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap($map);

    $payment_method = $this->createMock($payment_method_interface);
    $payment_method->expects($this->any())
      ->method('capturePaymentAccess')
      ->with($account)
      ->willReturn($payment_method_capture_access);

    $payment = $this->getMockPayment();
    $payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);

    $payment->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['payment']);

    $method = new \ReflectionMethod($this->sut, 'checkAccess');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * Provides data to self::testCheckAccessCapture().
   */
  public function providerTestCheckAccessCapture() {
    return array(
      array(TRUE, PaymentAccessUnitTestDummyPaymentMethodCapturePaymentInterface::class, TRUE, TRUE),
      array(FALSE, PaymentAccessUnitTestDummyPaymentMethodCapturePaymentInterface::class, FALSE, TRUE),
      array(FALSE, PaymentAccessUnitTestDummyPaymentMethodCapturePaymentInterface::class, TRUE, FALSE),
      array(FALSE, PaymentAccessUnitTestDummyPaymentMethodCapturePaymentInterface::class, FALSE, FALSE)
    );
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestCheckAccessRefund
   */
  public function testCheckAccessRefund($expected, $payment_method_interface, $payment_method_refund_access, $has_permissions) {
    $operation = 'refund';

    $account = $this->createMock(AccountInterface::class);
    $map = array(
      array('payment.payment.refund.any', $has_permissions),
      array('payment.payment.refund.own', $has_permissions),
    );
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap($map);

    $payment_method = $this->createMock($payment_method_interface);
    $payment_method->expects($this->any())
      ->method('refundPaymentAccess')
      ->with($account)
      ->willReturn($payment_method_refund_access);

    $payment = $this->getMockPayment();
    $payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);

    $payment->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['payment']);

    $method = new \ReflectionMethod($this->sut, 'checkAccess');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * Provides data to self::testCheckAccessRefund().
   */
  public function providerTestCheckAccessRefund() {
    return array(
      array(TRUE, PaymentAccessUnitTestDummyPaymentMethodRefundPaymentInterface::class, TRUE, TRUE),
      array(FALSE, PaymentAccessUnitTestDummyPaymentMethodRefundPaymentInterface::class, FALSE, TRUE),
      array(FALSE, PaymentAccessUnitTestDummyPaymentMethodRefundPaymentInterface::class, TRUE, FALSE),
      array(FALSE, PaymentAccessUnitTestDummyPaymentMethodRefundPaymentInterface::class, FALSE, FALSE)
    );
  }

  /**
   * @covers ::checkAccess
   */
  public function testCheckAccessUpdateStatusWithAccess() {
    $operation = 'update_status';

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->at(0))
      ->method('hasPermission')
      ->with('payment.payment.update_status.any')
      ->willReturn(TRUE);
    $account->expects($this->at(1))
      ->method('hasPermission')
      ->with('payment.payment.update_status.own')
      ->willReturn(FALSE);

    $payment_method = $this->createMock(PaymentAccessUnitTestDummyPaymentMethodUpdateStatusInterface::class);
    $payment_method->expects($this->once())
      ->method('updatePaymentStatusAccess')
      ->with($account)
      ->willReturn(TRUE);

    $payment = $this->getMockPayment();
    $payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);
    $payment->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['payment']);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * @covers ::checkAccess
   */
  public function testCheckAccessUpdateStatusWithoutAccess() {
    $operation = 'update_status';

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->never())
      ->method('hasPermission');

    $payment_method = $this->createMock(PaymentAccessUnitTestDummyPaymentMethodUpdateStatusInterface::class);
    $payment_method->expects($this->once())
      ->method('updatePaymentStatusAccess')
      ->with($account)
      ->willReturn(FALSE);

    $payment = $this->getMockPayment();
    $payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertFalse($method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestCheckAccessComplete
   */
  public function testCheckAccessComplete($expected_access, $account_id, $payment_owner_id, $payment_execution_has_completed) {
    $operation = 'complete';

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->any())
      ->method('id')
      ->willReturn($account_id);
    $account->expects($this->never())
      ->method('hasPermission');

    $payment_execution_result = $this->createMock(OperationResultInterface::class);
    $payment_execution_result->expects($this->any())
      ->method('isCompleted')
      ->willReturn($payment_execution_has_completed);

    $payment_method = $this->createMock(PaymentAccessUnitTestDummyPaymentMethodUpdateStatusInterface::class);
    $payment_method->expects($this->once())
      ->method('getPaymentExecutionResult')
      ->willReturn($payment_execution_result);

    $payment = $this->getMockPayment();
    $payment->expects($this->atLeastOnce())
      ->method('getOwnerId')
      ->willReturn($payment_owner_id);
    $payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertSame($expected_access, $method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * Provides data to self::testCheckAccessComplete()
   */
  public function providerTestCheckAccessComplete() {
    return [
      [TRUE, 7, 7, FALSE],
      [FALSE, 7, 7, TRUE],
      [FALSE, mt_rand(), mt_rand(), FALSE],
    ];
  }

  /**
   * @covers ::checkAccess
   */
  public function testCheckAccessCompleteWithoutPaymentMethod() {
    $operation = 'complete';

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->never())
      ->method('hasPermission');

    $payment = $this->getMockPayment();
    $payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn(NULL);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertFalse($method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * @covers ::checkAccess
   * @covers ::checkAccessPermission
   */
  public function testCheckAccessWithoutPermission() {
    $operation = $this->randomMachineName();
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturn(FALSE);
    $payment = $this->getMockPayment();
    $payment->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['payment']);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertFalse($method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * @covers ::checkAccess
   * @covers ::checkAccessPermission
   */
  public function testCheckAccessWithAnyPermission() {
    $operation = $this->randomMachineName();
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->at(0))
      ->method('hasPermission')
      ->with('payment.payment.' . $operation . '.any')
      ->willReturn(TRUE);
    $account->expects($this->at(1))
      ->method('hasPermission')
      ->with('payment.payment.' . $operation . '.own')
      ->willReturn(FALSE);

    $payment = $this->getMockPayment();
    $payment->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['payment']);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * @covers ::checkAccess
   * @covers ::checkAccessPermission
   */
  public function testCheckAccessWithOwnPermission() {
    $owner_id = mt_rand();
    $operation = $this->randomMachineName();
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->any())
      ->method('id')
      ->willReturn($owner_id);
    $map = array(
      array('payment.payment.' . $operation . '.any', FALSE),
      array('payment.payment.' . $operation . '.own', TRUE),
    );
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap($map);
    $payment = $this->getMockPayment();
    $payment->expects($this->at(0))
      ->method('getOwnerId')
      ->willReturn($owner_id);
    $payment->expects($this->at(1))
      ->method('getOwnerId')
      ->willReturn($owner_id + 1);
    $payment->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['payment']);


    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
    $this->assertFalse($method->invokeArgs($this->sut, array($payment, $operation, $account))->isAllowed());
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCheckCreateAccess() {
    $account = $this->createMock(AccountInterface::class);
    $context = [];

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkCreateAccess');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invokeArgs($this->sut, array($account, $context))->isAllowed());
  }

  /**
   * @covers ::getCache
   */
  public function testGetCache() {
    $account = $this->createMock(AccountInterface::class);
    $cache_id = $this->randomMachineName();
    $operation = $this->randomMachineName();
    $language_code = $this->randomMachineName();

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('getCache');
    $method->setAccessible(TRUE);
    $this->assertNull($method->invokeArgs($this->sut, array($cache_id, $operation, $language_code, $account)));
  }

}

/**
 * Extends two interfaces, because we can only mock one.
 */
interface PaymentAccessUnitTestDummyPaymentMethodUpdateStatusInterface extends PaymentMethodUpdatePaymentStatusInterface, PaymentMethodInterface {
}

/**
 * Extends two interfaces, because we can only mock one.
 */
interface PaymentAccessUnitTestDummyPaymentMethodCapturePaymentInterface extends PaymentMethodCapturePaymentInterface, PaymentMethodInterface {

}

/**
 * Extends two interfaces, because we can only mock one.
 */
interface PaymentAccessUnitTestDummyPaymentMethodRefundPaymentInterface extends PaymentMethodRefundPaymentInterface, PaymentMethodInterface {
}
