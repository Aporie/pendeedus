<?php

namespace Drupal\payment;

use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Event\PaymentEvents;
use Drupal\payment\Event\PaymentExecuteAccess;
use Drupal\payment\Event\PaymentPreCapture;
use Drupal\payment\Event\PaymentPreExecute;
use Drupal\payment\Event\PaymentPreRefund;
use Drupal\payment\Event\PaymentQueuePaymentIdsAlter;
use Drupal\payment\Event\PaymentStatusSet;
use Drupal\payment\Event\PaymentTypePreResumeContext;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

/**
 * Dispatches Payment events through Symfony's event dispatcher.
 */
class SymfonyEventDispatcher implements EventDispatcherInterface {

  /**
   * The Symfony event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $symfonyEventDispatcher;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $symfony_event_dispatcher
   */
  public function __construct(SymfonyEventDispatcherInterface $symfony_event_dispatcher) {
    $this->symfonyEventDispatcher = $symfony_event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQueueLoadedPaymentIds($queue_id, $category_id, $owner_id, array $payment_ids) {
    $event = new PaymentQueuePaymentIdsAlter($queue_id, $category_id, $owner_id, $payment_ids);
    $this->symfonyEventDispatcher->dispatch(PaymentEvents::PAYMENT_QUEUE_PAYMENT_IDS_ALTER, $event);
    $payment_ids = $event->getPaymentIds();

    return $payment_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentStatus(PaymentInterface $payment, PaymentStatusInterface $previous_payment_status = NULL) {
    $event = new PaymentStatusSet($payment, $previous_payment_status);
    $this->symfonyEventDispatcher->dispatch(PaymentEvents::PAYMENT_STATUS_SET, $event);
  }

  /**
   * {@inheritdoc}
   *
   */
  public function preExecutePayment(PaymentInterface $payment) {
    $event = new PaymentPreExecute($payment);
    $this->symfonyEventDispatcher->dispatch(PaymentEvents::PAYMENT_PRE_EXECUTE, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function executePaymentAccess(PaymentInterface $payment, PaymentMethodInterface $payment_method, AccountInterface $account) {
    $event = new PaymentExecuteAccess($payment, $payment_method, $account);
    $this->symfonyEventDispatcher->dispatch(PaymentEvents::PAYMENT_EXECUTE_ACCESS, $event);

    return $event->getAccessResult();
  }

  /**
   * {@inheritdoc}
   *
   */
  public function preCapturePayment(PaymentInterface $payment) {
    $event = new PaymentPreCapture($payment);
    $this->symfonyEventDispatcher->dispatch(PaymentEvents::PAYMENT_PRE_CAPTURE, $event);
  }

  /**
   * {@inheritdoc}
   *
   */
  public function preRefundPayment(PaymentInterface $payment) {
    $event = new PaymentPreRefund($payment);
    $this->symfonyEventDispatcher->dispatch(PaymentEvents::PAYMENT_PRE_REFUND, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function preResumeContext(PaymentInterface $payment) {
    $event = new PaymentTypePreResumeContext($payment);
    $this->symfonyEventDispatcher->dispatch(PaymentEvents::PAYMENT_TYPE_PRE_RESUME_CONTEXT, $event);
  }

}
