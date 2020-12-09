<?php

namespace Drupal\payment_reference;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface;

/**
 * Provides a payment factory service.
 */
class PaymentFactory implements PaymentFactoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The payment line item manager.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface
   */
  protected $paymentLineItemManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface $payment_line_item_manager
   *   The payment line item manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PaymentLineItemManagerInterface $payment_line_item_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->paymentLineItemManager = $payment_line_item_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(FieldDefinitionInterface $field_definition) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this->entityTypeManager
      ->getStorage('payment')
      ->create([
        'bundle' => 'payment_reference',
      ]);
    /** @var \Drupal\payment_reference\Plugin\Payment\Type\PaymentReference $payment_type */
    $payment_type = $payment->getPaymentType();
    $payment_type->setEntityTypeId($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId());
    $payment_type->setBundle($field_definition->getTargetBundle());
    $payment_type->setFieldName($field_definition->getName());
    $payment->setCurrencyCode($field_definition->getSetting('currency_code'));
    foreach ($field_definition->getSetting('line_items_data') as $line_item_data) {
      $line_item = $this->paymentLineItemManager->createInstance($line_item_data['plugin_id'], $line_item_data['plugin_configuration']);
      $payment->setLineItem($line_item);
    }

    return $payment;
  }
}
