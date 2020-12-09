<?php

namespace Drupal\Tests\payment\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\payment\DatabaseQueue;
use Drupal\payment\Tests\Generate;

/**
 * \Drupal\payment\DatabaseQueue test.
 *
 * @group Payment
 */
class DatabaseQueueWebTest extends KernelTestBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The payment method plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * The payment status plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * The payment reference queue service under test.
   *
   * @var \Drupal\payment\DatabaseQueue
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment', 'payment_test', 'currency', 'user', 'plugin');

  /**
   * {@inheritdoc}
   */
  function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('payment');
    $this->installSchema('payment', ['payment_queue']);
    $this->database = \Drupal::database();
    $this->paymentMethodManager = \Drupal::service('plugin.manager.payment.method');
    $this->paymentStatusManager = \Drupal::service('plugin.manager.payment.status');
    $queue_id = $this->randomMachineName();
    $this->queue = new DatabaseQueue($queue_id, $this->database, \Drupal::service('payment.event_dispatcher'), $this->paymentStatusManager);
  }

  /**
   * Tests queue service.
   */
  function testQueue() {
    $category_id_prefix = $this->randomMachineName();
    $category_id = $category_id_prefix . $this->randomMachineName();
    $payment = Generate::createPayment(2);
    $payment->setPaymentStatus($this->paymentStatusManager->createInstance('payment_success'));
    $payment->save();

    // Tests save().
    $this->queue->save($category_id, $payment->id());
    $payment_ids = $this->queue->loadPaymentIds($category_id, $payment->getOwnerId());
    $this->assertTrue(in_array($payment->id(), $payment_ids));

    // Tests claimPayment().
    $this->assertTrue(is_string($this->queue->claimPayment($payment->id())));
    $this->assertFalse($this->queue->claimPayment($payment->id()));
    $acquisition_code = $this->queue->claimPayment($payment->id());
    $this->assertTrue(is_string($acquisition_code));

    // Tests releaseClaim().
    $released = $this->queue->releaseClaim($payment->id(), $acquisition_code);
    $this->assertTrue($released);
    $acquisition_code = $this->queue->claimPayment($payment->id());
    $this->assertTrue(is_string($acquisition_code));

    // Tests acquirePayment().
    $acquired = $this->queue->acquirePayment($payment->id(), $acquisition_code);
    $this->assertTrue($acquired);
    $acquisition_code = $this->queue->claimPayment($payment->id());
    $this->assertFalse($acquisition_code);

    // Save another payment to the queue, because acquiring the previous one
    // deleted it.
    $payment = Generate::createPayment(2);
    $payment->setPaymentStatus($this->paymentStatusManager->createInstance('payment_success'));
    $payment->save();
    $this->queue->save($category_id, $payment->id());

    // Tests loadPaymentIds().
    $loaded_payment_ids = $this->queue->loadPaymentIds($category_id, $payment->getOwnerId());
    $this->assertEqual($loaded_payment_ids, array($payment->id()));

    // Tests deleteByPaymentId().
    $this->queue->deleteByPaymentId($payment->id());
    $payment_ids = $this->queue->loadPaymentIds($category_id, $payment->getOwnerId());
    $this->assertFalse(in_array($payment->id(), $payment_ids));

    // Tests deleteByCategoryIdPrefix().
    $this->queue->save($category_id, $payment->id());
    $this->queue->deleteByCategoryIdPrefix($category_id_prefix);
    $payment_ids = $this->queue->loadPaymentIds($category_id, $payment->getOwnerId());
    $this->assertFalse(in_array($payment->id(), $payment_ids));

    // Tests deleteByCategoryId().
    $this->queue->save($category_id, $payment->id());
    $this->queue->deleteByCategoryId($category_id);
    $payment_ids = $this->queue->loadPaymentIds($category_id, $payment->getOwnerId());
    $this->assertFalse(in_array($payment->id(), $payment_ids));
  }
}
