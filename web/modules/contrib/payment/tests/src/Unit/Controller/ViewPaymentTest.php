<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\payment\Controller\ViewPayment;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\ViewPayment
 *
 * @group Payment
 */
class ViewPaymentTest extends UnitTestCase {

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\ViewPayment
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new ViewPayment($this->stringTranslation);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('string_translation')
      ->willReturn($this->stringTranslation);

    $sut = ViewPayment::create($container);
    $this->assertInstanceOf(ViewPayment::class, $sut);
  }

  /**
   * @covers ::title
   */
  public function testTitle() {
    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->once())
      ->method('id');

    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->title($payment));
  }

}
