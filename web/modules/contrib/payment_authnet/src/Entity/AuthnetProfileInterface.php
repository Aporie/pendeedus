<?php

namespace Drupal\payment_authnet\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines Authnet profiles.
 */
interface AuthnetProfileInterface extends ConfigEntityInterface {

  /**
   * Gets the Authnet MerchantAuthentication.
   *
   * @return \net\authorize\api\contract\v1\MerchantAuthenticationType
   *   The Authorize.net Merchant Authentication object.
   */
  public function getMerchantAuthentication();

  /**
   * Gets the Authnet API Environment (sandbox/prod).
   *
   * @return string
   *   The URL to Authorize.net REST API endpoint.
   */
  public function getApiEnvironment();

  /**
   * Gets the Authnet API key.
   *
   * @return string
   *   The Authorize.net API Key or 'ref', if API key is empty.
   */
  public function getApiKey();

  /**
   * Returns Authorize.net API ID.
   *
   * @return string
   *   Authnet API ID.
   */
  public function getApiId();

  /**
   * Returns Transaction Key required for Authnet Merchant Authentication.
   *
   * @return string
   *   Transaction Key
   */
  public function getApiTransactionKey();

  /**
   * Returns Authnet Sandbox mode for this profile.
   *
   * @return bool
   *   TRUE if Authorize.net profile is in sandbox mode, FALSE otherwise.
   */
  public function getSandboxMode();

  /**
   * Sets Authnet Sandbox mode for this profile.
   *
   * @param mixed $sandboxMode
   *   TRUE / FALSE or 1/0.
   *
   * @return \Drupal\payment_authnet\Entity\AuthnetProfileInterface
   *   Returns $this.
   */
  public function setSandboxMode($sandboxMode);

}
