<?php

namespace Drupal\Tests\payment\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Payment;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface;

/**
 * \Drupal\payment\Entity\Payment unit test.
 *
 * @group Payment
 */
class PaymentUnitTest extends KernelTestBase {

  /**
   * The payment bundle to test with used for testing.
   *
   * @var string
   */
  protected $bundle = 'payment_unavailable';

  /**
   * The payment line item manager used for testing.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface
   */
  protected $lineItemManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('currency', 'field', 'payment', 'payment_test', 'plugin', 'system', 'user');

  /**
   * The payment under test.
   *
   * @var \Drupal\payment\Entity\Payment
   */
  protected $payment;

  /**
   * The payment status manager used for testing.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $statusManager;

  /**
   * {@inheritdoc
   */
  protected function setUp(): void {
    parent::setUp();
    $this->bundle = 'payment_unavailable';
    $this->lineItemManager = Payment::lineItemManager();
    $this->statusManager = Payment::statusManager();
    $this->payment = \Drupal\payment\Entity\Payment::create(array(
      'bundle' => $this->bundle,
    ));
  }

  /**
   * Tests label().
   */
  public function testLabel() {
    $this->assertIdentical($this->payment->label(), 'Unavailable');
  }

  /**
   * Tests bundle().
   */
  public function testBundle() {
    $this->assertIdentical($this->payment->bundle(), $this->bundle);
  }

  /**
   * Tests getPaymentType().
   */
  public function testGetPaymentType() {
    $this->assertTrue($this->payment->getPaymentType() instanceof PaymentTypeInterface);
    $this->assertIdentical($this->payment->getPaymentType()->getPluginId(), $this->bundle);
  }

  /**
   * Tests setCurrencyCode() and getCurrencyCode().
   */
  public function testGetCurrencyCode() {
    $currency_code = 'ABC';
    $this->assertTrue($this->payment->setCurrencyCode($currency_code) instanceof PaymentInterface);
    $this->assertIdentical($this->payment->getCurrencyCode(), $currency_code);
  }

  /**
   * Tests setLineItem() and getLineItem().
   */
  public function testGetLineItem() {
    $line_item = $this->lineItemManager->createInstance('payment_basic');
    $line_item->setName($this->randomMachineName());
    $this->assertTrue($this->payment->setLineItem($line_item) instanceof PaymentInterface);
    $this->assertIdentical(spl_object_hash($this->payment->getLineItem($line_item->getName())), spl_object_hash($line_item));
  }

  /**
   * Tests unsetLineItem().
   */
  public function testUnsetLineItem() {
    $line_item = $this->lineItemManager->createInstance('payment_basic');
    $name = $this->randomMachineName();
    $line_item->setName($name);
    $this->payment->setLineItem($line_item);
    $this->assertEqual(spl_object_hash($this->payment), spl_object_hash($this->payment->unsetLineItem($name)));
    $this->assertNull($this->payment->getLineItem($name));
  }

  /**
   * Tests setLineItems() and getLineItems().
   */
  public function testGetLineItems() {
    $line_item_1 = $this->lineItemManager->createInstance('payment_basic');
    $line_item_1->setName($this->randomMachineName());
    $line_item_2 = $this->lineItemManager->createInstance('payment_basic');
    $line_item_2->setName($this->randomMachineName());
    $this->assertEquals(spl_object_hash($this->payment->setLineItems([$line_item_1, $line_item_2])), spl_object_hash($this->payment));
    $line_items = $this->payment->getLineItems();
    $this->assertTrue(is_array($line_items));
    $this->assertEqual(spl_object_hash($line_items[$line_item_1->getName()]), spl_object_hash($line_item_1));
    $this->assertEqual(spl_object_hash($line_items[$line_item_2->getName()]), spl_object_hash($line_item_2));
  }

  /**
   * Tests getLineItemsByType().
   */
  public function testGetLineItemsByType() {
    $type = 'payment_basic';
    $line_item = $this->lineItemManager->createInstance($type);
    $this->assertEqual(spl_object_hash($this->payment->setLineItem($line_item)), spl_object_hash($this->payment));
    $line_items = $this->payment->getLineItemsByType($type);
    $this->assertEqual(spl_object_hash(reset($line_items)), spl_object_hash($line_item));
  }

  /**
   * Tests setPaymentStatus() and getPaymentStatus().
   */
  public function testGetPaymentStatus() {
    $payment_status_a = $this->statusManager->createInstance('payment_pending');
    $payment_status_b = $this->statusManager->createInstance('payment_failed');
    $this->assertEqual(spl_object_hash($this->payment->setPaymentStatus($payment_status_a)), spl_object_hash($this->payment));
    $this->assertEqual(spl_object_hash($this->payment->getPaymentStatus()), spl_object_hash($payment_status_a));
    // Make sure we always get the last status.
    $this->payment->setPaymentStatus($payment_status_b);
    $this->assertEqual(spl_object_hash($this->payment->getPaymentStatus()), spl_object_hash($payment_status_b));
  }

  /**
   * Tests setPaymentStatuses() and getPaymentStatuses().
   */
  public function testGetPaymentStatuses() {
    $statuses = array($this->statusManager->createInstance('payment_pending'), $this->statusManager->createInstance('payment_failed'));
    $this->assertEqual(spl_object_hash($this->payment->setPaymentStatuses($statuses)), spl_object_hash($this->payment));
    $retrieved_statuses = $this->payment->getPaymentStatuses();
    $this->assertEqual(spl_object_hash(reset($retrieved_statuses)), spl_object_hash(reset($statuses)));
    $this->assertEqual(spl_object_hash(end($retrieved_statuses)), spl_object_hash(end($statuses)));
    // Make sure we always get the last status.
    $this->assertEqual(spl_object_hash($this->payment->getPaymentStatus()), spl_object_hash(end($statuses)));
  }

  /**
   * Tests getPaymentMethod().
   */
  public function testGetPaymentMethod() {
    $payment_method = Payment::methodManager()->createInstance('payment_basic');
    $this->payment->setPaymentMethod($payment_method);
    $this->assertEqual(spl_object_hash($this->payment->getPaymentMethod()), spl_object_hash($payment_method));
    $this->assertEqual(spl_object_hash($this->payment->getPaymentMethod()->getPayment()), spl_object_hash($this->payment));
  }

  /**
   * Tests setOwnerId() and getOwnerId().
   */
  public function testGetOwnerId() {
    $id = 5;
    $this->assertTrue($this->payment->setOwnerId($id) instanceof PaymentInterface);
    $this->assertIdentical($this->payment->getOwnerId(), $id);
  }

  /**
   * Tests getAmount().
   */
  public function testGetAmount() {
    $amount = 3;
    $quantity = 2;
    for ($i = 0; $i < 2; $i++) {
      /** @var \Drupal\payment\Plugin\Payment\LineItem\Basic $line_item */
      $line_item = $this->lineItemManager->createInstance('payment_basic');
      $name = $this->randomMachineName();
      $line_item->setName($name);
      $line_item->setAmount($amount);
      $line_item->setQuantity($quantity);
      $this->payment->setLineItem($line_item);
    }
    $this->assertEqual($this->payment->getAmount(), 12);
  }
}
