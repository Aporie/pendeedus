<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Status;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\payment\Entity\PaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Status\ConfigDeriver;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Status\ConfigDeriver
 *
 * @group Payment
 */
class ConfigDeriverTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\ConfigDeriver
   */
  protected $sut;

  /**
   * The payment status storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentStatusStorage = $this->createMock(EntityStorageInterface::class);

    $this->sut = new ConfigDeriver($this->paymentStatusStorage);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('payment_status')
      ->willReturn($this->paymentStatusStorage);

    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_type_manager),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = ConfigDeriver::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(ConfigDeriver::class, $sut);
  }

  /**
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions() {
    $status_a = $this->createMock(PaymentStatusInterface::class);
    $status_a->expects($this->once())
      ->method('getDescription')
      ->willReturn($this->randomMachineName());
    $status_a->expects($this->once())
      ->method('id')
      ->willReturn($this->randomMachineName());
    $status_a->expects($this->once())
      ->method('label')
      ->willReturn($this->randomMachineName());
    $status_a->expects($this->once())
      ->method('getParentId')
      ->willReturn($this->randomMachineName());

    $status_b = $this->createMock(PaymentStatusInterface::class);
    $status_b->expects($this->once())
      ->method('getDescription')
      ->willReturn($this->randomMachineName());
    $status_b->expects($this->once())
      ->method('id')
      ->willReturn($this->randomMachineName());
    $status_b->expects($this->once())
      ->method('label')
      ->willReturn($this->randomMachineName());
    $status_b->expects($this->once())
      ->method('getParentId')
      ->willReturn($this->randomMachineName());

    $this->paymentStatusStorage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(array($status_a, $status_b));

    $derivatives = $this->sut->getDerivativeDefinitions([]);
    $this->assertCount(2, $derivatives);
  }
}
