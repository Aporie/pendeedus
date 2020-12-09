<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines payment method configurations.
 */
interface PaymentMethodConfigurationInterface extends ConfigEntityInterface, EntityOwnerInterface {

  /**
   * Sets the payment method configuration ID.
   *
   * @see \Drupal\Core\Entity\EntityInterface::id()
   *
   * @param string $id
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Sets the payment method UUID.
   *
   * @see \Drupal\Core\Entity\EntityInterface::uuid()
   *
   * @param string $uuid
   *
   * @return $this
   */
  public function setUuid($uuid);

  /**
   * Sets the human-readable label.
   *
   * @see \Drupal\Core\Entity\EntityInterface::label()
   *
   * @param string $label
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Sets the payment method configuration's plugin configuration.
   *
   * @param mixed[] $configuration
   *
   * @return static
   */
  public function setPluginConfiguration(array $configuration);

  /**
   * Gets the payment method configuration's plugin configuration.
   *
   * @return array
   */
  public function getPluginConfiguration();

  /**
   * Gets the payment method configuration's plugin ID.
   *
   * @return string
   */
  public function getPluginId();
}
