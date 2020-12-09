<?php

namespace Drupal\payment_reference;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines a payment factory service.
 */
interface PaymentFactoryInterface {

  /**
   * Creates a payment for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  public function createPayment(FieldDefinitionInterface $field_definition);

}
