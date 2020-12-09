<?php

namespace Drupal\Tests\payment\Unit\Event;

use Drupal\payment\Event\PaymentEvents;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Event\PaymentEvents
 *
 * @group Payment
 */
class PaymentEventsTest extends UnitTestCase {

  /**
   * Tests constants with event names.
   */
  public function testEventNames() {
    $class = new \ReflectionClass(PaymentEvents::class);
    foreach ($class->getConstants() as $event_name) {
      // Make sure that every event name is properly namespaced.
      $this->assertSame(0, strpos($event_name, 'drupal.payment.'));
    }
  }

}
