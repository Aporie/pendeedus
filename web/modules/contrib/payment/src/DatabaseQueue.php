<?php

namespace Drupal\payment;

use Drupal\Component\Utility\Random;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;

/**
 * Provides a database-based payment queue.
 */
class DatabaseQueue implements QueueInterface {

  /**
   * The IDs of the payment statuses available payments can have.
   *
   * @var string[]
   */
  protected $allowedPaymentStatusIds = array('payment_success');

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\payment\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The time it takes for a claim to expire.
   *
   * @var int
   *   A number of seconds.
   */
  protected $claimExpirationPeriod = 1;

  /**
   * The database connection.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * The random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomGenerator;

  /**
   * The unique ID of the queue (instance).
   *
   * @var string
   */
  protected $queueId;

  /**
   * Constructs a new instance.
   *
   * @param string $queue_id
   *   The unique ID of the queue (instance).
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\payment\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   *   The payment status plugin manager.
   */
  public function __construct($queue_id, Connection $database, EventDispatcherInterface $event_dispatcher, PaymentStatusManagerInterface $payment_status_manager) {
    $this->database = $database;
    $this->eventDispatcher = $event_dispatcher;
    $this->paymentStatusManager = $payment_status_manager;
    $this->randomGenerator = new Random();
    $this->queueId = $queue_id;
  }

  /**
   * Sets the claim expiration period.
   *
   * @param int $expiration_period
   *   A number of seconds.
   *
   * @return $this
   */
  public function setClaimExpirationPeriod($expiration_period) {
    $this->claimExpirationPeriod = $expiration_period;

    return $this;
  }

  /**
   * Gets the claim expiration period.
   *
   * @return int
   *   A number of seconds.
   */
  public function getClaimExpirationPeriod() {
    return $this->claimExpirationPeriod;
  }

  /**
   * Sets the allowed payment statuses.
   *
   * @param string[] $allowed_payment_status_ids
   *   The IDs of the payment statuses available payments can have.
   *
   * @return $this
   */
  public function setAllowedPaymentStatusIds(array $allowed_payment_status_ids) {
    $this->allowedPaymentStatusIds = $allowed_payment_status_ids;

    return $this;
  }

  /**
   * Gets the allowed payment statuses.
   *
   * @return string[]
   *   The IDs of the payment statuses available payments can have.
   */
  public function getAllowedPaymentStatusIds() {
    return $this->allowedPaymentStatusIds;
  }

  /**
   * {@inheritdoc}
   */
  function save($category_id, $payment_id) {
    $this->database->insert('payment_queue')
      ->fields(array(
        'category_id' => $category_id,
        'payment_id' => $payment_id,
        'queue_id' => $this->queueId,
      ))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function claimPayment($payment_id) {
    $acquisition_code = $this->tryClaimPaymentOnce($payment_id);
    // If a payment cannot be claimed at the first try, wait until the previous
    // claim has expired and try to claim the payment one more time.
    if ($acquisition_code === FALSE) {
      sleep($this->getClaimExpirationPeriod());
      $acquisition_code = $this->tryClaimPaymentOnce($payment_id);
    }

    return $acquisition_code;
  }

  /**
   * Tries to claim a payment once.
   *
   * @param integer $payment_id
   *
   * @return string|false
   *   An acquisition code to acquire the payment with on success, or FALSE if
   *   the payment could not be claimed.
   */
  protected function tryClaimPaymentOnce($payment_id) {
    $acquisition_code = $this->randomGenerator->string(255);
    $count = $this->database->update('payment_queue', array(
      'return' => Database::RETURN_AFFECTED,
    ))
      ->condition('claimed', time() - $this->getClaimExpirationPeriod(), '<')
      ->condition('payment_id', $payment_id)
      ->condition('queue_id', $this->queueId)
      ->fields(array(
        'acquisition_code' => $acquisition_code,
        'claimed' => time(),
      ))
      ->execute();

    return $count ? $acquisition_code : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function acquirePayment($payment_id, $acquisition_code) {
    return (bool) $this->database->delete('payment_queue', array(
      'return' => Database::RETURN_AFFECTED,
    ))
      ->condition('acquisition_code', $acquisition_code)
      ->condition('claimed', time() - $this->getClaimExpirationPeriod(), '>=')
      ->condition('payment_id', $payment_id)
      ->condition('queue_id', $this->queueId)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function releaseClaim($payment_id, $acquisition_code) {
    return (bool) $this->database->update('payment_queue', array(
      'return' => Database::RETURN_AFFECTED,
    ))
      ->condition('payment_id', $payment_id)
      ->condition('acquisition_code', $acquisition_code)
      ->condition('queue_id', $this->queueId)
      ->fields(array(
        'claimed' => 0,
      ))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  function loadPaymentIds($category_id, $owner_id) {
    $allowed_payment_status_ids = [];
    foreach ($this->getAllowedPaymentStatusIds() as $payment_status_id) {
      $allowed_payment_status_ids = array_merge($allowed_payment_status_ids, array($payment_status_id), $this->paymentStatusManager->getDescendants($payment_status_id));
    }
    if (empty($allowed_payment_status_ids)) {
      throw new \RuntimeException('There are no allowed payment statuses. Use self::setAllowedPaymentStatusIds() to set the allowed payment statuses.');
    }
    $query = $this->database->select('payment_queue', 'pq');
    $query->addJoin('INNER', 'payment', 'p', 'p.id = pq.payment_id');
    $query->addJoin('INNER', 'payment__payment_statuses', 'p_ps', 'p.id = p_ps.entity_id AND p.current_payment_status_delta = p_ps.delta');
    $query->fields('pq', array('payment_id'))
      ->condition('pq.category_id', $category_id)
      ->condition('p_ps.payment_statuses_plugin_id', $allowed_payment_status_ids)
      ->condition('p.owner', $owner_id)
      ->condition('pq.queue_id', $this->queueId);

    $payment_ids = $query->execute()->fetchCol();

    return $this->eventDispatcher->alterQueueLoadedPaymentIds($this->queueId, $category_id, $owner_id, $payment_ids);
  }

  /**
   * {@inheritdoc}
   */
  function deleteByPaymentId($id) {
    $this->database->delete('payment_queue')
      ->condition('payment_id', $id)
      ->condition('queue_id', $this->queueId)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  function deleteByCategoryId($category_id) {
    $this->database->delete('payment_queue')
      ->condition('category_id', $category_id)
      ->condition('queue_id', $this->queueId)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  function deleteByCategoryIdPrefix($category_id_prefix) {
    $this->database->delete('payment_queue')
      ->condition('category_id', $category_id_prefix . '%', 'LIKE')
      ->condition('queue_id', $this->queueId)
      ->execute();
  }
}
