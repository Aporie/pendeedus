<?php

namespace Drupal\payment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;

/**
 * Dispatches events to a collection of event dispatchers.
 */
class EventDispatcherCollection implements EventDispatcherInterface {

  /**
   * The event dispatchers.
   *
   * @var \Drupal\payment\EventDispatcherInterface[]
   */
  protected $eventDispatchers = [];

  /**
   * Adds an event dispatcher to the collection.
   *
   * @param \Drupal\payment\EventDispatcherInterface $event_dispatcher
   *
   * @return $this
   */
  public function addEventDispatcher(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatchers[] = $event_dispatcher;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQueueLoadedPaymentIds($queue_id, $category_id, $owner_id, array $payment_ids) {
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $payment_ids = $event_dispatcher->alterQueueLoadedPaymentIds($queue_id, $category_id, $owner_id, $payment_ids);
    }

    return $payment_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentStatus(PaymentInterface $payment, PaymentStatusInterface $previous_payment_status = NULL) {
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $event_dispatcher->setPaymentStatus($payment, $previous_payment_status);
    }
  }

  /**
   * {@inheritdoc}
   *
   */
  public function preExecutePayment(PaymentInterface $payment) {
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $event_dispatcher->preExecutePayment($payment);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function executePaymentAccess(PaymentInterface $payment, PaymentMethodInterface $payment_method, AccountInterface $account) {
    $access = AccessResult::neutral();
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $access = $access->orIf($event_dispatcher->executePaymentAccess($payment, $payment_method, $account));
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   *
   */
  public function preCapturePayment(PaymentInterface $payment) {
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $event_dispatcher->preCapturePayment($payment);
    }
  }

  /**
   * {@inheritdoc}
   *
   */
  public function preRefundPayment(PaymentInterface $payment) {
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $event_dispatcher->preRefundPayment($payment);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preResumeContext(PaymentInterface $payment) {
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $event_dispatcher->preResumeContext($payment);
    }
  }

}
