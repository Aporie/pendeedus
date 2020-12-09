<?php

namespace Drupal\Tests\payment\Unit\Event;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Event\PaymentPreRefund;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Event\PaymentPreRefund
 *
 * @group Payment
 */
class PaymentPreRefundTest extends UnitTestCase {

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Event\PaymentPreRefund
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->payment = $this->createMock(PaymentInterface::class);

    $this->sut = new PaymentPreRefund($this->payment);
  }

  /**
   * @covers ::getPayment
   */
  public function testGetPayment() {
    $this->assertSame($this->payment, $this->sut->getPayment());
  }

}
