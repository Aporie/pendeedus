<?php

namespace Drupal\Tests\payment\Unit\Event;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Event\PaymentTypePreResumeContext;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Event\PaymentTypePreResumeContext
 *
 * @group Payment
 */
class PaymentTypePreResumeContextTest extends UnitTestCase {

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Event\PaymentTypePreResumeContext
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->payment = $this->createMock(PaymentInterface::class);

    $this->sut = new PaymentTypePreResumeContext($this->payment);
  }

  /**
   * @covers ::getPayment
   */
  public function testGetPayment() {
    $this->assertSame($this->payment, $this->sut->getPayment());
  }

}
