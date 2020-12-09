<?php

namespace Drupal\payment_authnet;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment_authnet\Event\PaymentAuthnetEvents;
use Drupal\payment_authnet\Event\PaymentAuthnetPreVoid;
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
   *   Symfony event dispatcher.
   */
  public function __construct(SymfonyEventDispatcherInterface $symfony_event_dispatcher) {
    $this->symfonyEventDispatcher = $symfony_event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function preVoidPayment(PaymentInterface $payment) {
    $event = new PaymentAuthnetPreVoid($payment);
    $this->symfonyEventDispatcher->dispatch(PaymentAuthnetEvents::PAYMENT_AUTHNET_PRE_VOID, $event);
  }

}
