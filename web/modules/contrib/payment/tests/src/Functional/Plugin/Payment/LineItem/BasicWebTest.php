<?php

namespace Drupal\Tests\payment\Functional\Plugin\Payment\LineItem;

use Drupal\payment\Payment;
use Drupal\Tests\BrowserTestBase;

/**
 * \Drupal\payment\Plugin\Payment\LineItem\Basic web test.
 *
 * @group Payment
 */
class BasicWebTest extends BrowserTestBase {

  /**
   * The line item to test.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\Basic
   */
  protected $lineItem;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment', 'payment_test');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
 protected function setUp(): void {
    parent::setUp();
    $this->lineItem = Payment::lineItemManager()->createInstance('payment_basic');
  }

  /**
   * Tests the configuration form.
   */
  public function testConfigurationForm() {
    $line_item_data = array(
      'line_item[amount][amount]' => '123.45',
      'line_item[quantity]' => '3',
      'line_item[description]' => 'Foo & Bar',
    );

    // Tests the presence of child form elements and their default values.
    $this->drupalGet('payment_test-plugin-payment-line_item-payment_basic');
    foreach (array_keys($line_item_data) as $name) {
      $this->assertFieldByName($name);
    }

    // Test valid values.
    $data = $line_item_data;
    $data['line_item[description]'] = 'FooBar';
    $this->drupalPostForm('payment_test-plugin-payment-line_item-payment_basic', $data, t('Submit'));
    $this->assertUrl('user/login', [], 'Valid values trigger form submission.');

    // Test a non-integer quantity.
    $values =  array(
      'line_item[quantity]' => $this->randomMachineName(2),
    );
    $this->drupalPostForm('payment_test-plugin-payment-line_item-payment_basic', $values, t('Submit'));
    $this->assertFieldByXPath('//input[@name="line_item[quantity]" and contains(@class, "error")]');
  }
}
