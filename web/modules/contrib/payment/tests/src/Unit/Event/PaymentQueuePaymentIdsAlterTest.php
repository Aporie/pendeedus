<?php

namespace Drupal\Tests\payment\Unit\Event;

use Drupal\payment\Event\PaymentQueuePaymentIdsAlter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Event\PaymentQueuePaymentIdsAlter
 *
 * @group Payment
 */
class PaymentQueuePaymentIdsAlterTest extends UnitTestCase {

  /**
   * The queue category ID.
   *
   * @var string
   */
  protected $categoryId;

  /**
   * The ID of the user that must own the payments.
   *
   * @var int
   */
  protected $ownerId;

  /**
   * The IDs of available payments as loaded by the queue.
   *
   * @var int[]
   */
  protected $paymentIds;

  /**
   * The queue ID.
   *
   * @var string
   */
  protected $queueId;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Event\PaymentQueuePaymentIdsAlter
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->categoryId = $this->randomMachineName();

    $this->ownerId = $this->randomMachineName();

    $this->paymentIds = array($this->randomMachineName());

    $this->sut = new PaymentQueuePaymentIdsAlter($this->queueId, $this->categoryId, $this->ownerId, $this->paymentIds);
  }

  /**
   * @covers ::getQueueId
   */
  public function testGetQueueId() {
    $this->assertSame($this->queueId, $this->sut->getQueueId());
  }

  /**
   * @covers ::getCategoryId
   */
  public function testGetCategoryId() {
    $this->assertSame($this->categoryId, $this->sut->getCategoryId());
  }

  /**
   * @covers ::getOwnerId
   */
  public function testGetOwnerId() {
    $this->assertSame($this->ownerId, $this->sut->getOwnerId());
  }

  /**
   * @covers ::getPaymentIds
   * @covers ::setPaymentIds
   */
  public function testGetPaymentIds() {
    $this->assertSame($this->paymentIds, $this->sut->getPaymentIds());
    $payment_ids = array($this->randomMachineName());
    $this->assertSame($this->sut, $this->sut->setPaymentIds($payment_ids));
    $this->assertSame($payment_ids, $this->sut->getPaymentIds());

  }

}
