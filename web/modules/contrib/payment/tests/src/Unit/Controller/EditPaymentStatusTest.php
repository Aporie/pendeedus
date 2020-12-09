<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\payment\Controller\EditPaymentStatus;
use Drupal\payment\Entity\PaymentStatusInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\EditPaymentStatus
 *
 * @group Payment
 */
class EditPaymentStatusTest extends UnitTestCase {

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\EditPaymentStatus
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new EditPaymentStatus($this->stringTranslation);
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

    $sut = EditPaymentStatus::create($container);
    $this->assertInstanceOf(EditPaymentStatus::class, $sut);
  }

  /**
   * @covers ::title
   */
  public function testTitle() {
    $label = $this->randomMachineName();

    $payment_status = $this->createMock(PaymentStatusInterface::class);
    $payment_status->expects($this->once())
      ->method('label')
      ->willReturn($label);

    $this->assertStringContainsString($label, (string) $this->sut->title($payment_status));
  }

}
