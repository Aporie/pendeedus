<?php

namespace Drupal\payment_authnet;

use Drupal\payment\Entity\PaymentInterface;

/**
 * Dispatches events to a collection of event dispatchers.
 */
class EventDispatcherCollection implements EventDispatcherInterface {

  /**
   * The event dispatchers.
   *
   * @var \Drupal\payment_authnet\EventDispatcherInterface[]
   */
  protected $eventDispatchers = [];

  /**
   * Adds an event dispatcher to the collection.
   *
   * @param \Drupal\payment_authnet\EventDispatcherInterface $event_dispatcher
   *   Event Dispatcher.
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
  public function preVoidPayment(PaymentInterface $payment) {
    foreach ($this->eventDispatchers as $event_dispatcher) {
      $event_dispatcher->preVoidPayment($payment);
    }
  }

}
