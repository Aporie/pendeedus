<?php

namespace Drupal\payment\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Provides an event that alters
 * \Drupal\payment\QueueInterface::loadPaymentIds() results.
 *
 * @see \Drupal\payment\Event\PaymentEvents::PAYMENT_QUEUE_PAYMENT_IDS_ALTER
 */
class PaymentQueuePaymentIdsAlter extends Event {

  /**
   * The queue category ID.
   *
   * @var string
   */
  protected $categoryId;

  /**
   * The ID of the user that must own the payments.
   *
   * @var int
   */
  protected $ownerId;

  /**
   * The IDs of available payments as loaded by the queue.
   *
   * @var int[]
   */
  protected $paymentIds;

  /**
   * The queue ID.
   *
   * @var string
   */
  protected $queueId;

  /**-
   * Constructs a new instance.
   *
   * @param string $queue_id
   *   The queue ID.
   * @param string $category_id
   *   The queue category ID.
   * @param int $owner_id
   *   The ID of the user that must own the paymnets.
   * @param int[] $payment_ids
   *   The IDs of available payments as loaded by the queue.
   */
  public function __construct($queue_id, $category_id, $owner_id, array $payment_ids) {
    $this->categoryId = $category_id;
    $this->ownerId = $owner_id;
    $this->paymentIds = $payment_ids;
    $this->queueId = $queue_id;
  }

  /**
   * Gets the queue ID.
   *
   * @return string
   */
  public function getQueueId() {
    return $this->queueId;
  }

  /**
   * Gets the queue category ID.
   *
   * @return string
   */
  public function getCategoryId() {
    return $this->categoryId;
  }

  /**
   * Gets the ID of the user who owns the payments.
   *
   * @return int
   */
  public function getOwnerId() {
    return $this->ownerId;
  }

  /**
   * Gets the IDs of the available payments.
   *
   * @return int[]
   */
  public function getPaymentIds() {
    return $this->paymentIds;
  }

  /**
   * Sets the IDs of the available payments.
   *
   * @param int[] $payment_ids
   *
   * @return $this
   */
  public function setPaymentIds(array $payment_ids) {
    $this->paymentIds = $payment_ids;

    return $this;
  }

}
