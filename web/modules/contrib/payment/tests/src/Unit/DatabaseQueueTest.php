<?php

namespace Drupal\Tests\payment\Unit;

use Drupal\Core\Database\Connection;
use Drupal\payment\DatabaseQueue;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\DatabaseQueue
 *
 * @group Payment
 */
class DatabaseQueueTest extends UnitTestCase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\payment\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The database connection.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\DatabaseQueue
   */
  protected $sut;

  /**
   * The unique ID of the queue (instance).
   *
   * @var string
   */
  protected $queueId;

  /**
   * {@inheritdoc}
   */
  function setUp(): void {
    parent::setUp();
    $this->database = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

    $this->queueId = $this->randomMachineName();

    $this->sut = new DatabaseQueue($this->queueId, $this->database, $this->eventDispatcher, $this->paymentStatusManager);
  }

  /**
   * @covers ::getClaimExpirationPeriod
   * @covers ::setClaimExpirationPeriod
   */
  public function testGetClaimExpirationPeriod() {
    $expiration_period = mt_rand();
    $this->assertSame($this->sut, $this->sut->setClaimExpirationPeriod($expiration_period));
    $this->assertSame($expiration_period, $this->sut->getClaimExpirationPeriod());
  }

  /**
   * @covers ::getAllowedPaymentStatusIds
   * @covers ::setAllowedPaymentStatusIds
   */
  public function testGetAllowedPaymentStatusIds() {
    $allowed_payment_status_ids = array($this->randomMachineName(), $this->randomMachineName());
    $this->assertSame($this->sut, $this->sut->setAllowedPaymentStatusIds($allowed_payment_status_ids));
    $this->assertSame($allowed_payment_status_ids, $this->sut->getAllowedPaymentStatusIds());
  }

  /**
   * @covers ::claimPayment
   */
  public function testClaimPayment() {
    $payment_id = mt_rand();
    $acquisition_code = $this->randomMachineName();

    /** @var \Drupal\payment\DatabaseQueue|\PHPUnit\Framework\MockObject\MockObject $queue */
    $queue = $this->getMockBuilder(DatabaseQueue::class)
      ->setConstructorArgs(array($this->queueId, $this->database, $this->eventDispatcher, $this->paymentStatusManager))
      ->setMethods(array('tryClaimPaymentOnce'))
      ->getMock();
    $queue->expects($this->at(0))
      ->method('tryClaimPaymentOnce')
      ->with($payment_id)
      ->willReturn(FALSE);
    $queue->expects($this->at(1))
      ->method('tryClaimPaymentOnce')
      ->with($payment_id)
      ->willReturn($acquisition_code);

    $this->assertSame($acquisition_code, $queue->claimPayment($payment_id));
  }

}
