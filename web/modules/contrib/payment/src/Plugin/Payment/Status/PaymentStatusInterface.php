<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface.
 */

namespace Drupal\payment\Plugin\Payment\Status;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\payment\PaymentAwareInterface;

/**
 * A payment status plugin.
 *
 * Plugins can additionally implement the following interfaces:
 * - \Drupal\Core\Plugin\PluginFormInterface
 * - \Drupal\Component\Plugin\ConfigurableInterface
 *   Required if the plugin has any internal configuration, so it can be
 *   exported for recreation of the plugin at a later time.
 */
interface PaymentStatusInterface extends PluginInspectionInterface, PaymentAwareInterface {

  /**
   * Sets the created date and time.
   *
   * @param string $created
   *   A Unix timestamp.
   * *
   * @return static
   */
  public function setCreated($created);

  /**
   * Gets the created date and time.
   *
   * @return string
   *   A Unix timestamp.
   */
  public function getCreated();

  /**
   * Gets this payment status's ancestors.
   *
   * @return array
   *   The plugin IDs of this status's ancestors.
   */
  function getAncestors();

  /**
   * Gets this payment status's children.
   *
   * @return array
   *   The plugin IDs of this status's children.
   */
  public function getChildren();

  /**
   * Get this payment status's descendants.
   *
   * @return array
   *   The machine names of this status's descendants.
   */
  function getDescendants();

  /**
   * Checks if the status has a given other status as one of its ancestors.
   *.
   * @param string $plugin_id
   *   The payment status plugin ID to check against.
   *
   * @return boolean
   */
  function hasAncestor($plugin_id);

  /**
   * Checks if the status is equal to a given other status or has it one of
   * its ancestors.
   *
   * @param string $plugin_id
   *   The payment status plugin ID to check against.
   *
   * @return boolean
   */
  function isOrHasAncestor($plugin_id);
}
