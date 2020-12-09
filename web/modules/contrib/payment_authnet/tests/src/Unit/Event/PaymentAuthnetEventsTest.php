<?php

namespace Drupal\Tests\payment_authnet\Unit\Event;

use Drupal\payment_authnet\Event\PaymentAuthnetEvents;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment_authnet\Event\PaymentAuthnetEvents
 *
 * @group payment_authnet
 */
class PaymentAuthnetEventsTest extends UnitTestCase {

  /**
   * Tests constants with event names.
   */
  public function testEventNames() {
    $class = new \ReflectionClass(PaymentAuthnetEvents::class);
    foreach ($class->getConstants() as $event_name) {
      // Make sure that every event name is properly namespaced.
      $this->assertSame(0, strpos($event_name, 'drupal.payment.'));
    }
  }

}
