<?php

namespace Drupal\Tests\payment\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Event\PaymentEvents;
use Drupal\payment\Event\PaymentExecuteAccess;
use Drupal\payment\Event\PaymentPreCapture;
use Drupal\payment\Event\PaymentPreExecute;
use Drupal\payment\Event\PaymentPreRefund;
use Drupal\payment\Event\PaymentQueuePaymentIdsAlter;
use Drupal\payment\Event\PaymentStatusSet;
use Drupal\payment\Event\PaymentTypePreResumeContext;
use Drupal\payment\EventDispatcherCollection;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\payment\SymfonyEventDispatcher;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\EventDispatcherCollection
 *
 * @group Payment
 */
class EventDispatcherCollectionTest extends UnitTestCase {

  /**
   * The subject under test.
   *
   * @var \Drupal\payment\EventDispatcherCollection
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->sut = new EventDispatcherCollection();
  }

  /**
   * @covers ::alterQueueLoadedPaymentIds
   * @covers ::addEventDispatcher
   */
  public function testAlterQueueLoadedPaymentIds() {
    $queue_id = $this->randomMachineName();
    $category_id = $this->randomMachineName();
    $owner_id = mt_rand();
    $payment_ids = [mt_rand(), mt_rand(), mt_rand()];
    $payment_ids_a = [mt_rand(), mt_rand(), mt_rand()];
    $payment_ids_b = [mt_rand(), mt_rand(), mt_rand()];

    $event_dispatcher_a = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_a->expects($this->atLeastOnce())
      ->method('alterQueueLoadedPaymentIds')
      ->with($queue_id, $category_id, $owner_id, $payment_ids)
      ->willReturn($payment_ids_a);
    $event_dispatcher_b = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_b->expects($this->atLeastOnce())
      ->method('alterQueueLoadedPaymentIds')
      ->with($queue_id, $category_id, $owner_id, $payment_ids_a)
      ->willReturn($payment_ids_b);

    $this->sut->addEventDispatcher($event_dispatcher_a);
    $this->sut->addEventDispatcher($event_dispatcher_b);

    $this->assertSame($payment_ids_b, $this->sut->alterQueueLoadedPaymentIds($queue_id, $category_id, $owner_id, $payment_ids));
  }

  /**
   * @covers ::setPaymentStatus
   */
  public function testSetPaymentStatus() {
    $payment = $this->createMock(PaymentInterface::class);

    $previous_payment_status = $this->createMock(PaymentStatusInterface::class);

    $event_dispatcher_a = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_a->expects($this->atLeastOnce())
      ->method('setPaymentStatus')
      ->with($payment, $previous_payment_status);
    $event_dispatcher_b = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_b->expects($this->atLeastOnce())
      ->method('setPaymentStatus')
      ->with($payment, $previous_payment_status);

    $this->sut->addEventDispatcher($event_dispatcher_a);
    $this->sut->addEventDispatcher($event_dispatcher_b);

    $this->sut->setPaymentStatus($payment, $previous_payment_status);
  }

  /**
   * @covers ::preExecutePayment
   *
   */
  public function testPreExecutePayment() {
    $payment = $this->createMock(PaymentInterface::class);

    $event_dispatcher_a = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_a->expects($this->atLeastOnce())
      ->method('preExecutePayment')
      ->with($payment);
    $event_dispatcher_b = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_b->expects($this->atLeastOnce())
      ->method('preExecutePayment')
      ->with($payment);

    $this->sut->addEventDispatcher($event_dispatcher_a);
    $this->sut->addEventDispatcher($event_dispatcher_b);

    $this->sut->preExecutePayment($payment);
  }

  /**
   * @covers ::executePaymentAccess
   *
   * @dataProvider providerExecutePaymentAccess
   *
   * @param bool|null $expected_access
   *   TRUE for allowed, NULL for neutral, FALSE for forbidden.
   * @param \Drupal\Core\Access\AccessResult $event_dispatcher_access_a
   * @param \Drupal\Core\Access\AccessResult $event_dispatcher_access_b
   */
  public function testExecutePaymentAccess($expected_access, AccessResult $event_dispatcher_access_a, AccessResult $event_dispatcher_access_b) {
    $payment = $this->createMock(PaymentInterface::class);

    $payment_method = $this->createMock(PaymentMethodInterface::class);

    $account = $this->createMock(AccountInterface::class);

    $event_dispatcher_a = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_a->expects($this->atLeastOnce())
      ->method('executePaymentAccess')
      ->with($payment, $payment_method, $account)
      ->willReturn($event_dispatcher_access_a);
    $event_dispatcher_b = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_b->expects($this->atLeastOnce())
      ->method('executePaymentAccess')
      ->with($payment, $payment_method, $account)
      ->willReturn($event_dispatcher_access_b);

    $this->sut->addEventDispatcher($event_dispatcher_a);
    $this->sut->addEventDispatcher($event_dispatcher_b);

    $access = $this->sut->executePaymentAccess($payment, $payment_method, $account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    if ($expected_access === TRUE) {
      $this->assertTrue($access->isAllowed());
    }
    elseif ($expected_access === FALSE) {
      $this->assertTrue($access->isForbidden());
    }
    elseif ($expected_access === NULL) {
      $this->assertTrue($access->isNeutral());
    }
  }

  /**
   * Provides data to self::testExecutePaymentAccess
   */
  public function providerExecutePaymentAccess() {
    return [
      [TRUE, AccessResult::allowed(), AccessResult::allowed()],
      [TRUE, AccessResult::allowed(), AccessResult::neutral()],
      [TRUE, AccessResult::neutral(), AccessResult::allowed()],
      [NULL, AccessResult::neutral(), AccessResult::neutral()],
      [FALSE, AccessResult::allowed(), AccessResult::forbidden()],
      [FALSE, AccessResult::neutral(), AccessResult::forbidden()],
      [FALSE, AccessResult::forbidden(), AccessResult::allowed()],
      [FALSE, AccessResult::forbidden(), AccessResult::neutral()],
      [FALSE, AccessResult::forbidden(), AccessResult::forbidden()],
    ];
  }

  /**
   * @covers ::preCapturePayment
   */
  public function testPreCapturePayment() {
    $payment = $this->createMock(PaymentInterface::class);

    $event_dispatcher_a = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_a->expects($this->atLeastOnce())
      ->method('preCapturePayment')
      ->with($payment);
    $event_dispatcher_b = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_b->expects($this->atLeastOnce())
      ->method('preCapturePayment')
      ->with($payment);

    $this->sut->addEventDispatcher($event_dispatcher_a);
    $this->sut->addEventDispatcher($event_dispatcher_b);

    $this->sut->preCapturePayment($payment);
  }

  /**
   * @covers ::preRefundPayment
   */
  public function testPreRefundPayment() {
    $payment = $this->createMock(PaymentInterface::class);

    $event_dispatcher_a = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_a->expects($this->atLeastOnce())
      ->method('preRefundPayment')
      ->with($payment);
    $event_dispatcher_b = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_b->expects($this->atLeastOnce())
      ->method('preRefundPayment')
      ->with($payment);

    $this->sut->addEventDispatcher($event_dispatcher_a);
    $this->sut->addEventDispatcher($event_dispatcher_b);

    $this->sut->preRefundPayment($payment);
  }

  /**
   * @covers ::preResumeContext
   */
  public function testPreResumeContext() {
    $payment = $this->createMock(PaymentInterface::class);

    $event_dispatcher_a = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_a->expects($this->atLeastOnce())
      ->method('preResumeContext')
      ->with($payment);
    $event_dispatcher_b = $this->createMock(EventDispatcherInterface::class);
    $event_dispatcher_b->expects($this->atLeastOnce())
      ->method('preResumeContext')
      ->with($payment);

    $this->sut->addEventDispatcher($event_dispatcher_a);
    $this->sut->addEventDispatcher($event_dispatcher_b);

    $this->sut->preResumeContext($payment);
  }

}
