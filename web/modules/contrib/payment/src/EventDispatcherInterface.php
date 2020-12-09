<?php

namespace Drupal\payment;

use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;

/**
 * Defines a Payment event dispatcher.
 *
 * Because new events may be added in minor releases, this interface and all
 * classes that implemented are considered unstable forever. If you write an
 * event dispatcher, you must be prepared to update it in minor releases.
 */
interface EventDispatcherInterface {

  /**
   * Alters the payment IDs loaded by a payment queue.
   *
   * @param string $queue_id
   *   The ID of the queue to alter IDs for.
   * @param string $category_id
   *   The category of the IDs to alter.
   * @param int $owner_id
   *   The ID of the user for whom the IDs are retrieved.
   * @param int[] $payment_ids
   *   The IDs to alter.
   *
   * @return int[]
   *   The altered IDs.
   */
  public function alterQueueLoadedPaymentIds($queue_id, $category_id, $owner_id, array $payment_ids);

  /**
   * Responds to a new payment status being set.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment on which the new status has been set.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface|null $previous_payment_status
   *   The payment's previous status or NULL if there is none.
   */
  public function setPaymentStatus(PaymentInterface $payment, PaymentStatusInterface $previous_payment_status = NULL);

  /**
   * Fires right before a payment will be executed.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that will be executed.
   */
  public function preExecutePayment(PaymentInterface $payment);

  /**
   * Checks access for before executing a payment.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that will be executed.
   * @param \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface $payment_method
   *   The payment method that will execute the payment.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function executePaymentAccess(PaymentInterface $payment, PaymentMethodInterface $payment_method, AccountInterface $account);

  /**
   * Fires right before a payment will be captured.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that will be captured.
   */
  public function preCapturePayment(PaymentInterface $payment);

  /**
   * Fires right before a payment will be refunded.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment that will be refunded.
   */
  public function preRefundPayment(PaymentInterface $payment);

  /**
   * Fires right before a payment type's context is resumed.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment of which the type's context will be resumed.
   */
  public function preResumeContext(PaymentInterface $payment);

}
