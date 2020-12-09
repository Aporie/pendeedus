<?php

namespace Drupal\Tests\payment_authnet\Unit\Entity\Payment;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\payment_authnet\Entity\Payment\AuthnetVoidForm;
use Drupal\payment_authnet\Plugin\Payment\Method\AuthnetInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\OperationResultInterface;
use Drupal\payment\Response\ResponseInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\payment_authnet\Entity\Payment\AuthnetVoidForm
 *
 * @group payment_authnet
 */
class AuthnetVoidFormTest extends UnitTestCase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityRepository;

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\Payment|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $payment;


  /**
   * The payment method.
   *
   * @var \Drupal\payment_authnet\Plugin\Payment\Method\Authnet|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $paymentMethod;

  /**
   * The entity type bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $time;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_authnet\Entity\Payment\AuthnetVoidForm
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->entityRepository = $this->getMockBuilder(EntityRepositoryInterface::class)->getMock();
    $this->payment = $this->getMockBuilder(PaymentInterface::class)->getMock();
    $configuration = [
      'getCancelZeroAmount' => TRUE,
    ];
    $this->paymentMethod = $this->createConfiguredMock(AuthnetInterface::class, $configuration);
    $this->payment
      ->expects($this->any())
      ->method('getPaymentMethod')
      ->willReturn($this->paymentMethod);

    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class)->reveal();
    $this->time = $this->prophesize(TimeInterface::class)->reveal();

    $this->sut = new AuthnetVoidForm($this->entityRepository, $this->entityTypeBundleInfo, $this->time);
    $this->sut->setStringTranslation($this->getStringTranslationStub());
    $this->sut->setEntity($this->payment);
  }

  /**
   * @covers ::getDescription
   */
  public function testGetDescription() {
    $this->assertTrue(is_string($this->sut->getDescription()) || $this->sut->getDescription() instanceof TranslatableMarkup);
  }

  /**
   * @covers ::getQuestion
   */
  public function testGetQuestion() {
    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->getQuestion());
  }

  /**
   * @covers ::getCancelUrl
   */
  public function testGetCancelUrl() {
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
  public function testSubmitFormWithCompletionResponse() {
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

    $this->paymentMethod->expects($this->once())
      ->method('voidPayment')
      ->willReturn($operation_result);

    $form = [];

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('setResponse')
      ->with($response)
      ->willReturnSelf();

    $this->sut->submitForm($form, $form_state);
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormWithoutCompletionResponse() {
    $operation_result = $this->createMock(OperationResultInterface::class);
    $operation_result->expects($this->atLeastOnce())
      ->method('isCompleted')
      ->willReturn(TRUE);

    $this->paymentMethod->expects($this->once())
      ->method('voidPayment')
      ->willReturn($operation_result);

    $url = new Url($this->randomMachineName());

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
