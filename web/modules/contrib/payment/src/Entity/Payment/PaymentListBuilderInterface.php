<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\Core\Entity\EntityListBuilderInterface;

/**
 * Defines a payment list builder.
 */
interface PaymentListBuilderInterface extends EntityListBuilderInterface {

  /**
   * Restricts the displayed payments by owner ID.
   *
   * @param int $owner_id
   *
   * @return $this
   */
  public function restrictByOwnerId($owner_id);

}
