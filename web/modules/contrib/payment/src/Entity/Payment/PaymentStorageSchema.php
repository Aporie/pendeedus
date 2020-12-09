<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Provides a payment storage schema handler.
 */
class PaymentStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);
    $this->alterEntitySchemaWithNonFieldColumns($schema);

    return $schema;
  }

  /**
   * Adds non-field columns to the schema.
   *
   * @param array[] $schema
   *   The existing schema.
   */
  protected function alterEntitySchemaWithNonFieldColumns(array &$schema) {
    $schema['payment']['fields'] += array(
      'current_payment_status_delta' => array(
        'description' => "The {payment__payment_statuses}.delta of this payment's current status item.",
        'type' => 'int',
        'unsigned' => TRUE,
        'default' => 0,
        'not null' => TRUE,
      ),
    );
    $schema['payment']['foreign keys'] += array(
      'current_payment_status_delta' => array(
        'table' => 'payment__payment_statuses',
        'columns' => array(
          'current_payment_status_delta' => 'delta',
        ),
      ),
      'owner' => array(
        'table' => 'user',
        'columns' => array(
          'owner' => 'uid',
        ),
      ),
    );
  }

}
