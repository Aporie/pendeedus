<?php

namespace Drupal\Tests\payment\Unit;

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
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\payment\SymfonyEventDispatcher;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\payment\SymfonyEventDispatcher
 *
 * @group Payment
 */
class SymfonyEventDispatcherTest extends UnitTestCase {

  /**
   * The Symfony event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $symfonyEventDispatcher;

  /**
   * The subject under test.
   *
   * @var \Drupal\payment\SymfonyEventDispatcher
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->symfonyEventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->sut = new SymfonyEventDispatcher($this->symfonyEventDispatcher);
  }

  /**
   * @covers ::alterQueueLoadedPaymentIds
   */
  public function testAlterQueueLoadedPaymentIds() {
    $queue_id = $this->randomMachineName();
    $category_id = $this->randomMachineName();
    $owner_id = mt_rand();
    $payment_ids = [mt_rand(), mt_rand(), mt_rand()];

    $this->symfonyEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(PaymentEvents::PAYMENT_QUEUE_PAYMENT_IDS_ALTER, $this->isInstanceOf(PaymentQueuePaymentIdsAlter::class));

    $this->assertSame($payment_ids, $this->sut->alterQueueLoadedPaymentIds($queue_id, $category_id, $owner_id, $payment_ids));
  }

  /**
   * @covers ::setPaymentStatus
   */
  public function testSetPaymentStatus() {
    $payment = $this->createMock(PaymentInterface::class);

    $previous_payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->symfonyEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(PaymentEvents::PAYMENT_STATUS_SET, $this->isInstanceOf(PaymentStatusSet::class));

    $this->sut->setPaymentStatus($payment, $previous_payment_status);
  }

  /**
   * @covers ::preExecutePayment
   *
   */
  public function testPreExecutePayment() {
    $payment = $this->createMock(PaymentInterface::class);

    $this->symfonyEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(PaymentEvents::PAYMENT_PRE_EXECUTE, $this->isInstanceOf(PaymentPreExecute::class));

    $this->sut->preExecutePayment($payment);
  }

  /**
   * @covers ::executePaymentAccess
   */
  public function testExecutePaymentAccess() {
    $payment = $this->createMock(PaymentInterface::class);

    $payment_method = $this->createMock(PaymentMethodInterface::class);

    $account = $this->createMock(AccountInterface::class);

    $this->symfonyEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(PaymentEvents::PAYMENT_EXECUTE_ACCESS, $this->isInstanceOf(PaymentExecuteAccess::class));

    $this->assertInstanceOf(AccessResultInterface::class, $this->sut->executePaymentAccess($payment, $payment_method, $account));
  }

  /**
   * @covers ::preCapturePayment
   */
  public function testPreCapturePayment() {
    $payment = $this->createMock(PaymentInterface::class);

    $this->symfonyEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(PaymentEvents::PAYMENT_PRE_CAPTURE, $this->isInstanceOf(PaymentPreCapture::class));

    $this->sut->preCapturePayment($payment);
  }

  /**
   * @covers ::preRefundPayment
   */
  public function testPreRefundPayment() {
    $payment = $this->createMock(PaymentInterface::class);

    $this->symfonyEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(PaymentEvents::PAYMENT_PRE_REFUND, $this->isInstanceOf(PaymentPreRefund::class));

    $this->sut->preRefundPayment($payment);
  }

  /**
   * @covers ::preResumeContext
   */
  public function testPreResumeContext() {
    $payment = $this->createMock(PaymentInterface::class);

    $this->symfonyEventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with(PaymentEvents::PAYMENT_TYPE_PRE_RESUME_CONTEXT, $this->isInstanceOf(PaymentTypePreResumeContext::class));

    $this->sut->preResumeContext($payment);
  }

}
