<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;

/**
 * Defines a payment method configuration entity.
 *
 * @ConfigEntityType(
 *   bundle_label = @Translation("Payment method type"),
 *   handlers = {
 *     "access" = "Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationForm",
 *       "delete" = "Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationDeleteForm"
 *     },
 *     "list_builder" = "Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationListBuilder",
 *     "storage" = "\Drupal\Core\Config\Entity\ConfigEntityStorage",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "pluginId",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "ownerId",
 *     "pluginConfiguration",
 *     "pluginId",
 *     "status",
 *     "uuid",
 *   },
 *   id = "payment_method_configuration",
 *   label = @Translation("Payment method configuration"),
 *   links = {
 *     "enable" = "/admin/config/services/payment/method/configuration/{payment_method_configuration}/enable",
 *     "collection" = "/admin/config/services/payment/method/configuration",
 *     "disable" = "/admin/config/services/payment/method/configuration/{payment_method_configuration}/disable",
 *     "canonical" = "/admin/config/services/payment/method/configuration/{payment_method_configuration}",
 *     "edit-form" = "/admin/config/services/payment/method/configuration/{payment_method_configuration}",
 *     "delete-form" = "/admin/config/services/payment/method/configuration/{payment_method_configuration}/delete",
 *     "duplicate-form" = "/admin/config/services/payment/method/configuration/{payment_method_configuration}/duplicate"
 *   }
 * )
 */
class PaymentMethodConfiguration extends ConfigEntityBase implements PaymentMethodConfigurationInterface {

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
   * The UID of the user this payment method belongs to.
   *
   * @var integer
   */
  protected $ownerId;

  /**
   * The configuration, which comes from the entity's payment method plugin.
   *
   * @var array
   */
  protected $pluginConfiguration = [];

  /**
   * The bundle, which is the ID of the entity's payment method plugin.
   *
   * @var integer
   */
  protected $pluginId;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The entity's UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($id) {
    $this->ownerId = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $user) {
    $this->ownerId = $user->id();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->ownerId;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->getUserStorage()->load($this->getOwnerId());
  }

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
  public function setUuid($uuid) {
    $this->uuid = $uuid;

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
  public function setPluginConfiguration(array $configuration) {
    $this->pluginConfiguration = $configuration;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->pluginConfiguration;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += [
      'ownerId' => (int) \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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
      $this->entityTypeManager = \Drupal::entityTypeManager();
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

  /**
   * Sets the user storage.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *
   * @return $this
   */
  public function setUserStorage(UserStorageInterface $user_storage) {
    $this->userStorage = $user_storage;

    return $this;
  }

  /**
   * Gets the user storage.
   *
   * @return \Drupal\user\UserStorageInterface
   */
  protected function getUserStorage() {
    if (!$this->userStorage) {
      $this->userStorage = $this->entityTypeManager()->getStorage('user');
    }

    return $this->userStorage;
  }

}
