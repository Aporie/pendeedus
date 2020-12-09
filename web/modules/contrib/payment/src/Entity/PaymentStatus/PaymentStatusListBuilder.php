<?php

namespace Drupal\payment\Entity\PaymentStatus;

use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lists payment_status entities.
 */
class PaymentStatusListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Payment statuses are displayed by a custom controller. This list builder
    // is used solely for entity operations.
    throw new \Exception('This class is only used for entity operations and not for building lists.');
  }
}
