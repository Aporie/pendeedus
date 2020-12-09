<?php

namespace Drupal\payment\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a plugin collection for payment type plugins.
 *
 * @FieldType(
 *   id = "plugin:payment_status"
 * )
 */
class PaymentStatusItem extends PaymentAwarePluginCollectionItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['created'] = DataDefinition::create('payment_status_created')
      ->setLabel(t('Created'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['created'] = [
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
      'unsigned' => TRUE,
    ];

    return $schema;
  }

}
