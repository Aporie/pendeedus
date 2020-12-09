<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\payment\Controller\EditPaymentMethodConfiguration;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\EditPaymentMethodConfiguration
 *
 * @group Payment
 */
class EditPaymentMethodConfigurationTest extends UnitTestCase {

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\EditPaymentMethodConfiguration
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new EditPaymentMethodConfiguration($this->stringTranslation);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = EditPaymentMethodConfiguration::create($container);
    $this->assertInstanceOf(EditPaymentMethodConfiguration::class, $sut);
  }

  /**
   * @covers ::title
   */
  public function testTitle() {
    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_configuration->expects($this->once())
      ->method('label');

    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->title($payment_method_configuration));
  }

}
