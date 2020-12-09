<?php

namespace Drupal\payment\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;

/**
 * Provides a "current payment status" relationship.
 *
 * @ViewsRelationship("payment_current_status")
 */
class CurrentPaymentStatus extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // The current payment status item is uniquely identified by the entity ID
    // and the item delta. This plugin is already configured to use the delta
    // for the join. We only need to make sure the entity ID is added to that.
    $this->definition['extra'] = [[
      'field' => 'entity_id',
      'left_field' => 'id',
    ]];
    parent::query();
  }

}
