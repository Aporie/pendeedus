<?php

namespace Drupal\Tests\payment\Functional\Controller;

use Drupal\payment\Payment;
use Drupal\Tests\BrowserTestBase;

/**
 * Payment status UI.
 *
 * @group Payment
 */
class PaymentStatusWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment', 'block');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The payment status storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  public $paymentStatusStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->paymentStatusStorage = \Drupal::entityTypeManager()->getStorage('payment_status');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests listing.
   */
  public function testList() {
    $payment_status_id = strtolower($this->randomMachineName());
    /** @var \Drupal\payment\Entity\PaymentStatusInterface $status */
    $status = $this->paymentStatusStorage->create([]);
    $status->setId($payment_status_id)
      ->setLabel($this->randomMachineName())
      ->save();

    $path = 'admin/config/services/payment/status';
    $this->drupalGet($path);
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(array('payment.payment_status.administer')));
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Assert that the "Add payment status" link is visible.
    $this->assertLinkByHref('admin/config/services/payment/status/add');

    // Assert that all plugins are visible.
    $manager = Payment::statusManager();
    foreach ($manager->getDefinitions() as $definition) {
    $this->assertText($definition['label']);
      if ($definition['description']) {
        $this->assertText($definition['description']);
      }
    }

    // Assert that all config entity operations are visible.
    $this->assertLinkByHref('admin/config/services/payment/status/edit/' . $payment_status_id);
    $this->assertLinkByHref('admin/config/services/payment/status/delete/' . $payment_status_id);
  }

  /**
   * Tests adding and editing a payment status.
   */
  public function testAdd() {
    $path = 'admin/config/services/payment/status/add';
    $this->drupalGet($path);
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(array('payment.payment_status.administer')));
    $this->drupalGet($path);
    $this->assertResponse(200);

    // Test a valid submission.
    $payment_status_id = strtolower($this->randomMachineName());
    $label = $this->randomString();
    $parent_id = 'payment_success';
    $description = $this->randomString();
    $this->drupalPostForm($path, array(
      'label' => $label,
      'id' => $payment_status_id,
      'container[select][container][plugin_id]' => $parent_id,
      'description' => $description,
    ), t('Save'));
    /** @var \Drupal\payment\Entity\PaymentStatusInterface $status */
    $status = $this->paymentStatusStorage->loadUnchanged($payment_status_id);
    if ($this->assertTrue((bool) $status)) {
      $this->assertEqual($status->id(), $payment_status_id);
      $this->assertEqual($status->label(), $label);
      $this->assertEqual($status->getParentId(), $parent_id);
      $this->assertEqual($status->getDescription(), $description);
    }

    // Test editing a payment status.
    $this->drupalGet('admin/config/services/payment/status/edit/' . $payment_status_id);
    $this->assertLinkByHref('admin/config/services/payment/status/delete/' . $payment_status_id);
    $label = $this->randomString();
    $parent_id = 'payment_success';
    $description = $this->randomString();
    $this->drupalPostForm(NULL, array(
      'label' => $label,
      'container[select][container][plugin_id]' => $parent_id,
      'description' => $description,
    ), t('Save'));
    /** @var \Drupal\payment\Entity\PaymentStatusInterface $status */
    $status = $this->paymentStatusStorage->loadUnchanged($payment_status_id);
    if ($this->assertTrue((bool) $status)) {
      $this->assertEqual($status->id(), $payment_status_id);
      $this->assertEqual($status->label(), $label);
      $this->assertEqual($status->getParentId(), $parent_id);
      $this->assertEqual($status->getDescription(), $description);
    }

    // Test an invalid submission.
    $this->drupalPostForm($path, array(
      'label' => $label,
      'id' => $payment_status_id,
    ), t('Save'));
    $this->assertFieldByXPath('//input[@id="edit-id" and contains(@class, "error")]');
  }

  /**
   * Tests deleting a payment status.
   */
  public function testDelete() {
    $payment_status_id = strtolower($this->randomMachineName());
    /** @var \Drupal\payment\Entity\PaymentStatusInterface $status */
    $status = $this->paymentStatusStorage->create([]);
    $status->setId($payment_status_id)
      ->save();

    $path = 'admin/config/services/payment/status/delete/' . $payment_status_id;
    $this->drupalGet($path);
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(array('payment.payment_status.administer')));
    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertNull($this->paymentStatusStorage->loadUnchanged($payment_status_id));
  }
}
