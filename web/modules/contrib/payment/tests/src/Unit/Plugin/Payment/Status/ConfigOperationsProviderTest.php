<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Status;

use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\payment\Entity\PaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Status\ConfigOperationsProvider;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Status\ConfigOperationsProvider
 *
 * @group Payment
 */
class ConfigOperationsProviderTest extends UnitTestCase {

  /**
   * The payment status list builder.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusListBuilder;

  /**
   * The payment status storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusStorage;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\ConfigOperationsProvider
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentStatusListBuilder = $this->createMock(EntityListBuilderInterface::class);

    $this->paymentStatusStorage = $this->createMock(EntityStorageInterface::class);

    $this->sut = new ConfigOperationsProvider($this->paymentStatusStorage, $this->paymentStatusListBuilder);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreate() {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('payment_status')
      ->willReturn($this->paymentStatusStorage);
    $entity_type_manager->expects($this->once())
      ->method('getListBuilder')
      ->with('payment_status')
      ->willReturn($this->paymentStatusListBuilder);

    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('entity_type.manager')
      ->willReturn($entity_type_manager);

    $this->assertInstanceOf(ConfigOperationsProvider::class, ConfigOperationsProvider::create($container));
  }

    /**
     * @covers ::getOperations
     */
    public function testGetOperations() {
    $entity_id = $this->randomMachineName();
    $plugin_id = 'payment_config:' . $entity_id;

    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusStorage->expects($this->once())
      ->method('load')
      ->with($entity_id)
      ->willReturn($payment_status);

    $operations = array(
      'foo' => array(
        'title' => $this->randomMachineName(),
      ),
    );
    $this->paymentStatusListBuilder->expects($this->once())
      ->method('getOperations')
      ->with($payment_status)
      ->willReturn($operations);

    $this->assertSame($operations, $this->sut->getOperations($plugin_id));
  }

}
