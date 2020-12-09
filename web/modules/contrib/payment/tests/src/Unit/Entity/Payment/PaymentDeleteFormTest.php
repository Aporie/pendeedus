<?php

namespace Drupal\Tests\payment\Unit\Entity\Payment {

  use Drupal\Component\Datetime\TimeInterface;
  use Drupal\Core\Entity\EntityRepositoryInterface;
  use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Messenger\MessengerInterface;
  use Drupal\Core\StringTranslation\TranslatableMarkup;
  use Drupal\Core\Url;
  use Drupal\payment\Entity\Payment\PaymentDeleteForm;
  use Drupal\payment\Entity\PaymentInterface;
  use Drupal\Tests\UnitTestCase;
  use Psr\Log\LoggerInterface;

  /**
   * @coversDefaultClass \Drupal\payment\Entity\Payment\PaymentDeleteForm
   *
   * @group Payment
   */
  class PaymentDeleteFormTest extends UnitTestCase {

    /**
     * The entity repository.
     *
     * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityRepository;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * The payment.
     *
     * @var \Drupal\payment\Entity\PaymentInterface|\PHPUnit\Framework\MockObject\MockObject
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
     * The messenger.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messenger;

    /**
     * The class under test.
     *
     * @var \Drupal\payment\Entity\Payment\PaymentDeleteForm
     */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

      $this->logger = $this->createMock(LoggerInterface::class);

      $this->payment = $this->createMock(PaymentInterface::class);

      $this->stringTranslation = $this->getStringTranslationStub();
      $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class)->reveal();
      $this->time = $this->prophesize(TimeInterface::class)->reveal();
      $this->messenger = $this->createMock(MessengerInterface::class);

      $this->sut = new PaymentDeleteForm($this->entityRepository, $this->entityTypeBundleInfo, $this->time);
      $this->sut->setStringTranslation($this->stringTranslation);
      $this->sut->setEntity($this->payment);
      $this->sut->setLogger($this->logger);
      $this->sut->setMessenger($this->messenger);
    }

    /**
     * @covers ::getQuestion
     */
    function testGetQuestion() {
      $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->getQuestion());
    }

    /**
     * @covers ::getConfirmText
     */
    function testGetConfirmText() {
      $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->getConfirmText());
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

      $cancel_url = $this->sut->getCancelUrl();
      $this->assertSame($url, $cancel_url);
    }

    /**
     * @covers ::submitForm
     */
    function testSubmitForm() {
      $this->logger->expects($this->atLeastOnce())
        ->method('info');

      $this->payment->expects($this->once())
        ->method('delete');

      $form = [];
      $form_state = $this->createMock(FormStateInterface::class);
      $form_state->expects($this->once())
        ->method('setRedirect')
        ->with('<front>');

      $this->sut->submitForm($form, $form_state);
    }

  }

}
