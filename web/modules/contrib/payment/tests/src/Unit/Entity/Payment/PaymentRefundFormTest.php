<?php

namespace Drupal\Tests\payment\Unit\Entity\Payment;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\payment\Entity\Payment\PaymentRefundForm;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\OperationResultInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodRefundPaymentInterface;
use Drupal\payment\Response\ResponseInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\payment\Entity\Payment\PaymentRefundForm
 *
 * @group Payment
 */
class PaymentRefundFormTest extends UnitTestCase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityRepository;

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\Payment|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The entity type bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Entity\Payment\PaymentRefundForm
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->payment = $this->createMock(PaymentInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class)->reveal();
    $this->time = $this->prophesize(TimeInterface::class)->reveal();

    $this->sut = new PaymentRefundForm($this->entityRepository, $this->entityTypeBundleInfo, $this->time);
    $this->sut->setStringTranslation($this->stringTranslation);
    $this->sut->setEntity($this->payment);
  }

  /**
   * @covers ::getConfirmText
   */
  function testGetConfirmText() {
    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->getConfirmText());
  }

  /**
   * @covers ::getQuestion
   */
  function testGetQuestion() {
    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->getQuestion());
  }

  /**
   * @covers ::getCancelUrl
   */
  function testGetCancelUrl() {
    $url = new Url($this->randomMachineName());

    $this->payment->expects($this->atLeastOnce())
      ->method('toUrl')
      ->with('canonical')
      ->willReturn($url);

    $this->assertSame($url, $this->sut->getCancelUrl());
  }

  /**
   * @covers ::submitForm
   */
  function testSubmitFormWithCompletionResponse() {
    $response = $this->getMockBuilder(Response::class)
      ->disableOriginalConstructor()
      ->getMock();

    $completion_response = $this->createMock(ResponseInterface::class);
    $completion_response->expects($this->atLeastOnce())
      ->method('getResponse')
      ->willReturn($response);

    $operation_result = $this->createMock(OperationResultInterface::class);
    $operation_result->expects($this->atLeastOnce())
      ->method('getCompletionResponse')
      ->willReturn($completion_response);
    $operation_result->expects($this->atLeastOnce())
      ->method('isCompleted')
      ->willReturn(FALSE);

    $payment_method = $this->createMock(PaymentMethodRefundPaymentInterface::class);
    $payment_method->expects($this->once())
      ->method('refundPayment')
      ->willReturn($operation_result);

    $this->payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);

    $form = [];

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('setResponse')
      ->with($response);

    $this->sut->submitForm($form, $form_state);
  }

  /**
   * @covers ::submitForm
   */
  function testSubmitFormWithoutCompletionResponse() {
    $operation_result = $this->createMock(OperationResultInterface::class);
    $operation_result->expects($this->atLeastOnce())
      ->method('isCompleted')
      ->willReturn(TRUE);

    $payment_method = $this->createMock(PaymentMethodRefundPaymentInterface::class);
    $payment_method->expects($this->once())
      ->method('refundPayment')
      ->willReturn($operation_result);

    $url = new Url($this->randomMachineName());

    $this->payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);
    $this->payment->expects($this->atLeastOnce())
      ->method('toUrl')
      ->with('canonical')
      ->willReturn($url);

    $form = [];

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('setRedirectUrl')
      ->with($url);

    $this->sut->submitForm($form, $form_state);
  }

}
