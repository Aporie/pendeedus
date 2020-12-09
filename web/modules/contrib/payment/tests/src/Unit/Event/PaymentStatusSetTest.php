<?php

namespace Drupal\Tests\payment\Unit\Event;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Event\PaymentStatusSet;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Event\PaymentStatusSet
 *
 * @group Payment
 */
class PaymentStatusSetTest extends UnitTestCase {

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The previous payment status.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface|null
   */
  protected $previousPaymentStatus;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Event\PaymentStatusSet
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->payment = $this->createMock(PaymentInterface::class);

    $this->createMock(PaymentStatusInterface::class);

    $this->sut = new PaymentStatusSet($this->payment, $this->previousPaymentStatus);
  }

  /**
   * @covers ::getPayment
   */
  public function testGetPayment() {
    $this->assertSame($this->payment, $this->sut->getPayment());
  }

  /**
   * @covers ::getPreviousPaymentStatus
   */
  public function testGetPreviousPaymentStatus() {
    $this->assertSame($this->previousPaymentStatus, $this->sut->getPreviousPaymentStatus());
  }

}
