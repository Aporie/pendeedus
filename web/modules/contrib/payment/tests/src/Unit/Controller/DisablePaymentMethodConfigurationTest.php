<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Url;
use Drupal\payment\Controller\DisablePaymentMethodConfiguration;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @coversDefaultClass \Drupal\payment\Controller\DisablePaymentMethodConfiguration
 *
 * @group Payment
 */
class DisablePaymentMethodConfigurationTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\DisablePaymentMethodConfiguration
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->sut = new DisablePaymentMethodConfiguration();
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $url = 'http://example.com/' . $this->randomMachineName();

    $urlObject = $this->getMockBuilder(Url::class)->disableOriginalConstructor()->getMock();
    $urlObject->expects($this->once())
      ->method('setAbsolute')
      ->with(TRUE)
      ->willReturn($urlObject);
    $urlObject->expects($this->once())
      ->method('toString')
      ->willReturn($url);

    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_configuration->expects($this->atLeastOnce())
      ->method('disable');
    $payment_method_configuration->expects($this->atLeastOnce())
      ->method('save');
    $payment_method_configuration->expects($this->atLeastOnce())
      ->method('toUrl')
      ->with('collection')
      ->willReturn($urlObject);

    $response = $this->sut->execute($payment_method_configuration);
    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertSame($url, $response->getTargetUrl());
  }

}
