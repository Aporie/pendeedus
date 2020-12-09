<?php

namespace Drupal\Tests\payment_reference\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface;
use Drupal\payment_reference\PaymentFactory;
use Drupal\payment_reference\Plugin\Payment\Type\PaymentReference;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment_reference\PaymentFactory
 *
 * @group Payment Reference Field
 */
class PaymentFactoryTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The payment line item manager.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentLineItemManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_reference\PaymentFactory
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->paymentLineItemManager = $this->createMock(PaymentLineItemManagerInterface::class);

    $this->sut = new PaymentFactory($this->entityTypeManager, $this->paymentLineItemManager);
  }

  /**
   * @covers ::createPayment
   */
  public function testCreatePayment() {
    $bundle = $this->randomMachineName();
    $currency_code = $this->randomMachineName();
    $entity_type_id = $this->randomMachineName();
    $field_name = $this->randomMachineName();

    $payment_type = $this->getMockBuilder(PaymentReference::class)
      ->disableOriginalConstructor()
      ->getMock();
    $payment_type->expects($this->once())
      ->method('setBundle')
      ->with($bundle);
    $payment_type->expects($this->once())
      ->method('setEntityTypeId')
      ->with($entity_type_id);
    $payment_type->expects($this->once())
      ->method('setFieldName')
      ->with($field_name);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->once())
      ->method('setCurrencyCode')
      ->with($currency_code);
    $payment->expects($this->once())
      ->method('getPaymentType')
      ->willReturn($payment_type);

    $payment_storage = $this->createMock(EntityStorageInterface::class);
    $payment_storage->expects($this->once())
      ->method('create')
      ->with(array(
        'bundle' => 'payment_reference',
      ))
      ->willReturn($payment);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('payment')
      ->willReturn($payment_storage);

    $line_item_a = $this->createMock(PaymentLineItemInterface::class);
    $line_item_plugin_id_a = $this->randomMachineName();
    $line_item_plugin_configuration_a = array(
      'foo' => $this->randomMachineName(),
    );
    $line_item_b = $this->createMock(PaymentLineItemInterface::class);
    $line_item_plugin_id_b = $this->randomMachineName();
    $line_item_plugin_configuration_b = array(
      'bar' => $this->randomMachineName(),
    );

    $field_storage_definition = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_storage_definition->expects($this->once())
      ->method('getTargetEntityTypeId')
      ->willReturn($entity_type_id);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->expects($this->once())
      ->method('getTargetBundle')
      ->willReturn($bundle);
    $field_definition->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($field_storage_definition);
    $field_definition->expects($this->once())
      ->method('getName')
      ->willreturn($field_name);
    $map = array(
      array('currency_code', $currency_code),
      array('line_items_data', array(
        array(
          'plugin_configuration' => $line_item_plugin_configuration_a,
          'plugin_id' => $line_item_plugin_id_a,
        ),
        array(
          'plugin_configuration' => $line_item_plugin_configuration_b,
          'plugin_id' => $line_item_plugin_id_b,
        ),
      )),
    );
    $field_definition->expects($this->exactly(2))
      ->method('getSetting')
      ->willReturnMap($map);

    $this->paymentLineItemManager->expects($this->at(0))
      ->method('createInstance')
      ->with($line_item_plugin_id_a, $line_item_plugin_configuration_a)
      ->willReturn($line_item_a);
    $this->paymentLineItemManager->expects($this->at(1))
      ->method('createInstance')
      ->with($line_item_plugin_id_b, $line_item_plugin_configuration_b)
      ->willReturn($line_item_b);

    $this->assertSame($payment, $this->sut->createPayment($field_definition));
  }
}
