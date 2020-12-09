<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a payment status entity.
 *
 * @ConfigEntityType(
 *   admin_permission = "payment.payment_status.administer",
 *   handlers = {
 *     "access" = "\Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\payment\Entity\PaymentStatus\PaymentStatusForm",
 *       "delete" = "Drupal\payment\Entity\PaymentStatus\PaymentStatusDeleteForm"
 *     },
 *     "list_builder" = "Drupal\payment\Entity\PaymentStatus\PaymentStatusListBuilder",
 *     "storage" = "\Drupal\Core\Config\Entity\ConfigEntityStorage"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "description",
 *     "id",
 *     "label",
 *     "parentId",
 *     "uuid",
 *   },
 *   id = "payment_status",
 *   label = @Translation("Payment status"),
 *   links = {
 *     "canonical" = "/admin/config/services/payment/status/edit/{payment_status}",
 *     "collection" = "/admin/config/services/payment/type",
 *     "edit-form" = "/admin/config/services/payment/status/edit/{payment_status}",
 *     "delete-form" = "/admin/config/services/payment/status/edit/{payment_status}/delete"
 *   }
 * )
 */
class PaymentStatus extends ConfigEntityBase implements PaymentStatusInterface {

  /**
   * The status' description.
   *
   * @var string
   */
  protected $description;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity's unique machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin ID of the parent payment status.
   *
   * @var string
   */
  protected $parentId;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The entity's UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentId($id) {
    $this->parentId = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return $this->parentId;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityTypeManager() {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = parent::entityTypeManager();
    }

    return $this->entityTypeManager;
  }

  /**
   * Sets the typed config.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *
   * @return $this
   */
  public function setTypedConfig(TypedConfigManagerInterface $typed_config_manager) {
    $this->typedConfigManager = $typed_config_manager;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTypedConfig() {
    if (!$this->typedConfigManager) {
      $this->typedConfigManager = parent::getTypedConfig();
    }

    return $this->typedConfigManager;
  }

}
