<?php

namespace Drupal\payment_authnet\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\payment_authnet\Exception\PaymentAuthnetSdkException;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use net\authorize\api\constants\ANetEnvironment;

/**
 * Defines a Authorize.net profile entity.
 *
 * @ConfigEntityType(
 *   admin_permission = "administer payment authnet",
 *   handlers = {
 *     "access" = "\Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\payment_authnet\Entity\AuthnetProfile\AuthnetProfileForm",
 *       "delete" = "Drupal\payment_authnet\Entity\AuthnetProfile\AuthnetProfileDeleteForm"
 *     },
 *     "list_builder" = "Drupal\payment_authnet\Entity\AuthnetProfile\AuthnetProfileListBuilder",
 *     "storage" = "\Drupal\Core\Config\Entity\ConfigEntityStorage"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "apiId" = "apiId",
 *     "apiTransactionKey" = "apiTransactionKey",
 *     "apiKey" = "apiKey",
 *     "sandboxMode" = "sandboxMode",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "apiId",
 *     "apiTransactionKey",
 *     "apiKey",
 *     "sandboxMode",
 *     "uuid"
 *   },
 *   id = "authnet_profile",
 *   label = @Translation("Authorize.net Profile"),
 *   links = {
 *     "canonical" = "/admin/config/services/payment/authnet/profiles/edit/{authnet_profile}",
 *     "collection" = "/admin/config/services/payment/authnet/profiles",
 *     "edit-form" = "/admin/config/services/payment/authnet/profiles/edit/{authnet_profile}",
 *     "delete-form" = "/admin/config/services/payment/authnet/profiles/edit/{authnet_profile}/delete"
 *   }
 * )
 */
class AuthnetProfile extends ConfigEntityBase implements AuthnetProfileInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity's unique machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var string
   */
  protected $label;

  /**
   * The Authnet API Login ID for this profile.
   *
   * @var string
   */
  protected $apiId;

  /**
   * The Authnet Transaction Key for this profile.
   *
   * @var string
   */
  protected $apiTransactionKey;

  /**
   * The Authnet Key for this profile.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * The Authnet Environment for this profile.
   *
   * @var string
   */
  protected $apiEnvironment;

  /**
   * The Authnet Sandbox mode for this profile.
   *
   * @var string
   */
  protected $sandboxMode;

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
   * The Authnet Merchant Authentication.
   *
   * @var \net\authorize\api\contract\v1\MerchantAuthenticationType
   */
  protected $merchantAuthentication;

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
  public function setApiKey($apiKey) {
    $this->apiKey = $apiKey;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setApiId($apiId) {
    $this->apiId = $apiId;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setApiTransactionKey($apiTransactionKey) {
    $this->apiTransactionKey = $apiTransactionKey;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSandboxMode($sandboxMode) {
    $this->sandboxMode = (bool) $sandboxMode;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function setApiEnvironment($sandboxMode = NULL) {
    if ($sandboxMode !== NULL) {
      $this->setSandboxMode($sandboxMode);
    }

    $this->apiEnvironment = is_null($this->sandboxMode) || !$this->sandboxMode
      ? ANetEnvironment::PRODUCTION
      : ANetEnvironment::SANDBOX;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiKey() {
    if (!$this->apiKey) {
      $this->setApiKey('ref');
    }
    return $this->apiKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiId() {
    return $this->apiId;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiTransactionKey() {
    return $this->apiTransactionKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getSandboxMode() {
    return (bool) $this->sandboxMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiEnvironment() {
    if (!$this->apiEnvironment) {
      $this->setApiEnvironment($this->getSandboxMode());
    }
    return $this->apiEnvironment;
  }

  /**
   * Sets the Authnet MerchantAuthentication.
   *
   * @return $this
   */
  protected function setMerchantAuthentication() {
    try {
      $this->merchantAuthentication = new MerchantAuthenticationType();
      $this->merchantAuthentication->setName($this->getApiId());
      $this->merchantAuthentication->setTransactionKey($this->getApiTransactionKey());
    }
    catch (PaymentAuthnetSdkException $e) {
      \Drupal::logger('payment_authnet')->error($e->getMessage());
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMerchantAuthentication() {
    if (!$this->merchantAuthentication) {
      $this->setMerchantAuthentication();
    }

    return $this->merchantAuthentication;
  }

}
