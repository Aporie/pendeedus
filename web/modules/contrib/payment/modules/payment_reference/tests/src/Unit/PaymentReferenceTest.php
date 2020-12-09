<?php

namespace Drupal\Tests\payment_reference\Unit;

use Drupal\payment\QueueInterface;
use Drupal\payment_reference\PaymentFactoryInterface;
use Drupal\payment_reference\PaymentReference;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\payment_reference\PaymentReference
 *
 * @group Payment Reference Field
 */
class PaymentReferenceTest extends UnitTestCase {

  /**
   * @covers ::queue
   */
  public function testQueue() {
    $container = new Container();
    $queue = $this->createMock(QueueInterface::class);
    $container->set('payment_reference.queue', $queue);
    \Drupal::setContainer($container);
    $this->assertSame($queue, PaymentReference::queue());
  }

  /**
   * @covers ::factory
   */
  public function testFactory() {
    $container = new Container();
    $factory = $this->createMock(PaymentFactoryInterface::class);
    $container->set('payment_reference.payment_factory', $factory);
    \Drupal::setContainer($container);
    $this->assertSame($factory, PaymentReference::factory());
  }

}
