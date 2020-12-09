<?php

namespace Drupal\Tests\payment\Functional\Entity\Payment;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Payment;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface;
use Drupal\payment\plugin\payment\status\PaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface;
use Drupal\payment\Tests\Generate;
use Drupal\Tests\BrowserTestBase;

/**
 * \Drupal\payment\Entity\Payment\PaymentStorage web test.
 *
 * @group Payment
 */
class PaymentStorageWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests CRUD();
   */
  public function testCRUD() {
    $database = \Drupal::database();
    $user = $this->drupalCreateUser();
    $payment_type_configuration = array(
      $this->randomMachineName() => $this->randomMachineName(),
    );
    $payment_method = Payment::methodManager()->createInstance('payment_basic:no_payment_required');

    // Test creating a payment.
    $payment = Generate::createPayment($user->id(), $payment_method);
    $payment->getPaymentType()->setConfiguration($payment_type_configuration);
    $this->assertTrue($payment instanceof PaymentInterface);
    // @todo The ID should be an integer, but for some reason the entity field
    //   API returns a string.
    $this->assertTrue(is_numeric($payment->getOwnerId()));
    $this->assertEqual(count($payment->validate()), 0);
    $this->assertTrue($payment->getPaymentType() instanceof PaymentTypeInterface);

    // Test saving a payment.
    $this->assertNull($payment->id());
    // Set an extra status, so we can test for status IDs later.
    $payment->setPaymentStatus(Payment::statusManager()->createInstance('payment_success'));
    $payment->save();
    $this->assertTrue(is_numeric($payment->id()));
    $this->assertNotEmpty($payment->uuid());
    $this->assertEquals($user->id(), $payment->getOwnerId());
    // Check references to other tables.
    $payment_data = $database->select('payment', 'p')
      ->fields('p', array('current_payment_status_delta'))
      ->condition('id', $payment->id())
      ->execute()
      ->fetchAssoc();
    $this->assertEqual($payment_data['current_payment_status_delta'], 1);
    /** @var \Drupal\payment\Entity\PaymentInterface $payment_loaded */
    $payment_loaded = \Drupal\payment\Entity\Payment::load($payment->id());
    $this->assertEqual(count($payment_loaded->getLineItems()), count($payment->getLineItems()));
    foreach ($payment_loaded->getLineItems() as $line_item) {
      $this->assertTrue($line_item instanceof PaymentLineItemInterface);
    }
    $this->assertEqual(count($payment_loaded->getPaymentStatuses()), count($payment->getPaymentStatuses()));
    foreach ($payment_loaded->getPaymentStatuses() as $status) {
      $this->assertTrue($status instanceof PaymentStatusInterface);
    }
    $this->assertEqual($payment_loaded->getPaymentMethod()->getConfiguration(), $payment->getPaymentMethod()->getConfiguration());
    $this->assertEqual($payment_loaded->getPaymentType()->getConfiguration(), $payment->getPaymentType()->getConfiguration());
  }
}
