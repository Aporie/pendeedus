<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines payment statuses.
 */
interface PaymentStatusInterface extends ConfigEntityInterface {

  /**
   * Sets the payment method ID.
   *
   * @see \Drupal\Core\Entity\EntityInterface::id()
   *
   * @param string $id
   *
   * @return \Drupal\payment\Entity\PaymentStatusInterface
   */
  public function setId($id);

  /**
   * Sets the human-readable label.
   *
   * @see \Drupal\Core\Entity\EntityInterface::label()
   *
   * @param string $label
   *
   * @return \Drupal\payment\Entity\PaymentStatusInterface
   */
  public function setLabel($label);

  /**
   * Sets the parent plugin's ID.
   *
   * @param string $id
   *
   * @return \Drupal\payment\Entity\PaymentStatusInterface
   */
  public function setParentId($id);

  /**
   * Gets the parent plugin's ID.
   *
   * @return string
   */
  public function getParentId();

  /**
   * Sets description.
   *
   * @param string $description
   *
   * @return \Drupal\payment\Entity\PaymentStatusInterface
   */
  public function setDescription($description);

  /**
   * Gets the description.
   *
   * @return string
   */
  public function getDescription();
}
