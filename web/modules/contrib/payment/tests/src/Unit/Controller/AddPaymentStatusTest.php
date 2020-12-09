<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\payment\Controller\AddPaymentStatus;
use Drupal\payment\Entity\PaymentStatusInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\AddPaymentStatus
 *
 * @group Payment
 */
class AddPaymentStatusTest extends UnitTestCase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFormBuilder;

  /**
   * The payment status storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusStorage;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\AddPaymentStatus
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityFormBuilder = $this->createMock(EntityFormBuilderInterface::class);

    $this->paymentStatusStorage = $this->createMock(EntityStorageInterface::class);

    $this->sut = new AddPaymentStatus($this->entityFormBuilder, $this->paymentStatusStorage);
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
    $map = [
      ['entity.form_builder', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->entityFormBuilder],
      ['entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_type_manager],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = AddPaymentStatus::create($container);
    $this->assertInstanceOf(AddPaymentStatus::class, $sut);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->paymentStatusStorage->expects($this->once())
      ->method('create')
      ->willReturn($payment_status);

    $form = $this->createMock(FormInterface::class);

    $this->entityFormBuilder->expects($this->once())
      ->method('getForm')
      ->with($payment_status)
      ->willReturn($form);

    $this->assertSame($form, $this->sut->execute());
  }

}
