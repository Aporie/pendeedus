<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\payment\Controller\DuplicatePaymentMethodConfiguration;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\DuplicatePaymentMethodConfiguration
 *
 * @group Payment
 */
class DuplicatePaymentMethodConfigurationTest extends UnitTestCase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFormBuilder;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\DuplicatePaymentMethodConfiguration
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityFormBuilder = $this->createMock(EntityFormBuilderInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new DuplicatePaymentMethodConfiguration($this->stringTranslation, $this->entityFormBuilder);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['entity.form_builder', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->entityFormBuilder],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = DuplicatePaymentMethodConfiguration::create($container);
    $this->assertInstanceOf(DuplicatePaymentMethodConfiguration::class, $sut);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_configuration->expects($this->once())
      ->method('createDuplicate')
      ->willReturnSelf();
    $payment_method_configuration->expects($this->once())
      ->method('setLabel')
      ->willReturnSelf();

    $form = $this->createMock(EntityFormInterface::class);

    $this->entityFormBuilder->expects($this->once())
      ->method('getForm')
      ->with($payment_method_configuration, 'default')
      ->willReturn($form);

    $this->sut->execute($payment_method_configuration);
  }

  /**
   * @covers ::title
   */
  public function testTitle() {
    $label = $this->randomMachineName();

    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_configuration->expects($this->once())
      ->method('label')
      ->willReturn($label);

    $this->assertStringContainsString($label, (string) $this->sut->title($payment_method_configuration));
  }

}
