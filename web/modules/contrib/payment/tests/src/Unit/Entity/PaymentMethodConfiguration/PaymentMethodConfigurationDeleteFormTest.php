<?php

namespace Drupal\Tests\payment\Unit\Entity\PaymentMethodConfiguration {

  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Messenger\MessengerInterface;
  use Drupal\Core\StringTranslation\TranslatableMarkup;
  use Drupal\Core\Url;
  use Drupal\payment\Entity\PaymentMethodConfiguration;
  use Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationDeleteForm;
  use Drupal\Tests\UnitTestCase;
  use Psr\Log\LoggerInterface;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * @coversDefaultClass \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationDeleteForm
   *
   * @group Payment
   */
  class PaymentMethodConfigurationDeleteFormTest extends UnitTestCase {

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * The payment.
     *
     * @var \Drupal\payment\Entity\PaymentMethodConfigurationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodConfiguration;

    /**
     * The string translator.
     *
     * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $stringTranslation;

    /**
     * The messenger.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messenger;

    /**
     * The class under test.
     *
     * @var \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationDeleteForm
     */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      $this->logger = $this->createMock(LoggerInterface::class);

      $this->paymentMethodConfiguration = $this->getMockBuilder(PaymentMethodConfiguration::class)
        ->disableOriginalConstructor()
        ->getMock();

      $this->stringTranslation = $this->getStringTranslationStub();

      $this->messenger = $this->createMock(MessengerInterface::class);

      $this->sut = new PaymentMethodConfigurationDeleteForm($this->stringTranslation, $this->logger);
      $this->sut->setEntity($this->paymentMethodConfiguration);
      $this->sut->setMessenger($this->messenger);
    }

    /**
     * @covers ::create
     * @covers ::__construct
     */
    function testCreate() {
      $container = $this->createMock(ContainerInterface::class);
      $map = [
        ['payment.logger', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->logger],
        ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
      ];
      $container->expects($this->any())
        ->method('get')
        ->willReturnMap($map);

      $form = PaymentMethodConfigurationDeleteForm::create($container);
      $this->assertInstanceOf(PaymentMethodConfigurationDeleteForm::class, $form);
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

      $this->paymentMethodConfiguration->expects($this->atLeastOnce())
        ->method('toUrl')
        ->with('collection')
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

      $url = new Url($this->randomMachineName());

      $this->paymentMethodConfiguration->expects($this->once())
        ->method('delete');
      $this->paymentMethodConfiguration->expects($this->atLeastOnce())
        ->method('toUrl')
        ->with('collection')
        ->willReturn($url);

      $form = [];
      $form_state = $this->createMock(FormStateInterface::class);
      $form_state->expects($this->once())
        ->method('setRedirectUrl')
        ->with($url);

      $this->sut->submitForm($form, $form_state);
    }

  }

}
