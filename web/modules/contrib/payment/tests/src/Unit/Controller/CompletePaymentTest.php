<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\payment\Controller\CompletePayment;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\OperationResultInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Response\ResponseInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\payment\Controller\CompletePayment
 *
 * @group Payment
 */
class CompletePaymentTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\CompletePayment
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->sut = new CompletePayment();
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $response = $this->getMockBuilder(Response::class)
      ->disableOriginalConstructor()
      ->getMock();

    $completion_response = $this->createMock(ResponseInterface::class);
    $completion_response->expects($this->atLeastOnce())
      ->method('getResponse')
      ->willReturn($response);

    $execution_result = $this->createMock(OperationResultInterface::class);
    $execution_result->expects($this->atLeastOnce())
      ->method('getCompletionResponse')
      ->willReturn($completion_response);

    $payment_method = $this->createMock(PaymentMethodInterface::class);
    $payment_method->expects($this->atLeastOnce())
      ->method('getPaymentExecutionResult')
      ->willReturn($execution_result);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);

    $this->assertSame($response, $this->sut->execute($payment));
  }

}
