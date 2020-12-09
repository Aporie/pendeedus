<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Method;

use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\payment\Plugin\Payment\Method\BasicOperationsProvider;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Method\BasicOperationsProvider
 *
 * @group Payment
 */
class BasicOperationsProviderTest extends UnitTestCase {

  /**
   * The payment method configuration list builder.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationListBuilder;

  /**
   * The payment method configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationStorage;

  /**
   * The provider under test
   *
   * @var \Drupal\payment\Plugin\Payment\Method\BasicOperationsProvider
   */
  protected $sut;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $redirectDestination;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentMethodConfigurationListBuilder = $this->createMock(EntityListBuilderInterface::class);

    $this->paymentMethodConfigurationStorage = $this->createMock(EntityStorageInterface::class);

    $this->redirectDestination = $this->createMock(RedirectDestinationInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new BasicOperationsProvider($this->stringTranslation, $this->redirectDestination, $this->paymentMethodConfigurationStorage, $this->paymentMethodConfigurationListBuilder);
  }

  /**
   * @covers ::getPaymentMethodConfiguration
   */
  public function testGetPaymentMethodConfiguration() {
    $entity_id = $this->randomMachineName();
    $plugin_id = 'payment_basic:' . $entity_id;

    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);

    $this->paymentMethodConfigurationStorage->expects($this->once())
      ->method('load')
      ->with($entity_id)
      ->willReturn($payment_method_configuration);

    $method = new \ReflectionMethod($this->sut, 'getPaymentMethodConfiguration');
    $method->setAccessible(TRUE);
    $this->assertEquals($payment_method_configuration, $method->invoke($this->sut, $plugin_id));
  }

}
