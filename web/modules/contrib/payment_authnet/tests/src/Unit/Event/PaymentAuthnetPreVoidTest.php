<?php

namespace Drupal\Tests\payment_authnet\Unit\Event;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment_authnet\Event\PaymentAuthnetPreVoid;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment_authnet\Event\PaymentAuthnetPreVoid
 *
 * @group payment_authnet
 */
class PaymentAuthnetPreVoidTest extends UnitTestCase {

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $payment;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_authnet\Event\PaymentAuthnetPreVoid
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->payment = $this->getMockBuilder(PaymentInterface::class)->getMock();

    $this->sut = new PaymentAuthnetPreVoid($this->payment);
  }

  /**
   * @covers ::getPayment
   */
  public function testGetPayment() {
    $this->assertSame($this->payment, $this->sut->getPayment());
  }

}
