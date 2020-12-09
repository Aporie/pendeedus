<?php

namespace Drupal\payment\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItem;

/**
 * Provides a plugin item for payment-aware plugins.
 */
class PaymentAwarePluginCollectionItem extends PluginCollectionItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['plugin_instance'] = MapDataDefinition::create('payment_aware_plugin_instance')
      ->setLabel(t('Plugin instance'))
      ->setComputed(TRUE);

    return $properties;
  }

}
