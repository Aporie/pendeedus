<?php

namespace Drupal\payment;

/**
 * Defines a payment queue.
 */
interface QueueInterface {

  /**
   * Saves a payment available for referencing.
   *
   * @param string $category_id
   *   The ID of the category the payment falls in.
   * @param integer $payment_id
   */
  public function save($category_id, $payment_id);

  /**
   * Claims a payment available for referencing through a field instance.
   *
   * After a payment has been claimed, it can be definitely acquired with
   * self::acquirePayment().
   *
   * @param integer $payment_id
   *
   * @return string|false
   *   An acquisition code to acquire the payment with on success, or FALSE if
   *   the payment could not be claimed.
   */
  public function claimPayment($payment_id);

  /**
   * Releases a claimed payment.
   *
   * @param integer $payment_id
   * @param string $acquisition_code
   *   The code that was received from self::claim().
   */
  public function releaseClaim($payment_id, $acquisition_code);

  /**
   * Acquires a payment and removes if from the queue.
   *
   * @param integer $payment_id
   * @param string $acquisition_code
   *   The code that was received from self::claimPayment().
   *
   * @return bool
   *   Whether the acquisition was successful.
   */
  public function acquirePayment($payment_id, $acquisition_code);

  /**
   * Loads the IDs of payments available for referencing through an instance.
   *
   * @param string $category_id
   *   The ID of the field instance to load payment IDs for.
   * @param integer $owner_id
   *   The UID of the user for whom the payment should be available.
   *
   * @return array
   */
  public function loadPaymentIds($category_id, $owner_id);

  /**
   * Deletes a payment from the queue by payment ID.
   *
   * @param integer $payment_id
   */
  public function deleteByPaymentId($payment_id);

  /**
   * Deletes payments from the queue by category ID.
   *
   * @param string $category_id
   */
  public function deleteByCategoryId($category_id);

  /**
   * Deletes payments from the queue by category ID prefix.
   *
   * @param string $category_id_prefix
   */
  public function deleteByCategoryIdPrefix($category_id_prefix);
}
