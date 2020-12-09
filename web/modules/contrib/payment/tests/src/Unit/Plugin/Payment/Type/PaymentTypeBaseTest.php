<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Type;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeBase;
use Drupal\payment\Response\ResponseInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Type\PaymentTypeBase
 *
 * @group Payment
 */
class PaymentTypeBaseTest extends UnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Drupal\payment\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = $this->getMockBuilder(PaymentTypeBase::class)
      ->setConstructorArgs([$configuration, $plugin_id, $plugin_definition, $this->eventDispatcher])
      ->getMockForAbstractClass();
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['payment.event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    /** @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeBase $class_name */
    $class_name = get_class($this->sut);

    $sut = $class_name::create($container, [], $this->randomMachineName(), []);
    $this->assertInstanceOf(PaymentTypeBase::class, $sut);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->assertSame([], $this->sut->calculateDependencies());
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $this->assertSame([], $this->sut->defaultConfiguration());
  }

  /**
   * @covers ::setConfiguration
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $configuration = [
      'foo' => $this->randomMachineName(),
    ];
    $this->assertNull($this->sut->setConfiguration($configuration));
    $this->assertSame($configuration, $this->sut->getConfiguration());
  }

  /**
   * @covers ::setPayment
   * @covers ::getPayment
   */
  public function testGetPayment() {
    $payment = $this->createMock(PaymentInterface::class);
    $this->assertSame($this->sut, $this->sut->setPayment($payment));
    $this->assertSame($payment, $this->sut->getPayment());
  }

  /**
   * @covers ::getResumeContextResponse
   *
   * @depends testGetPayment
   */
  public function testGetResumeContextResponse() {
    $response = $this->createMock(ResponseInterface::class);

    $payment = $this->createMock(PaymentInterface::class);

    $this->sut->setPayment($payment);

    $this->sut->expects($this->atLeastOnce())
      ->method('doGetResumeContextResponse')
      ->willReturn($response);

    $this->eventDispatcher->expects($this->once())
      ->method('preResumeContext')
      ->with($payment);

    $this->assertSame($response, $this->sut->getResumeContextResponse());
  }
}
