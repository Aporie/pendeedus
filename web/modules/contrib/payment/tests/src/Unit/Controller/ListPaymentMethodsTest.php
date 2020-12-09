<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\payment\Controller\ListPaymentMethods;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\ListPaymentMethods
 *
 * @group Payment
 */
class ListPaymentMethodsTest extends UnitTestCase {

  /**
   * The payment method plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodManager;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\ListPaymentMethods
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->paymentMethodManager = $this->createMock(PaymentMethodManagerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new ListPaymentMethods($this->stringTranslation, $this->paymentMethodManager);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['plugin.manager.payment.method', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodManager],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = ListPaymentMethods::create($container);
    $this->assertInstanceOf(ListPaymentMethods::class, $sut);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_id_b = $this->randomMachineName();
    $definitions = [
      $plugin_id_a => [
        'active' => TRUE,
        'class' => $this->getMockClass(PaymentMethodInterface::class),
        'label' => $this->randomMachineName(),
      ],
      $plugin_id_b => [
        'active' => FALSE,
        'class' => $this->getMockClass(PaymentMethodInterface::class),
        'label' => $this->randomMachineName(),
      ],
    ];

    $this->paymentMethodManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $build = $this->sut->execute();
    $this->assertIsArray($build);
  }

}
