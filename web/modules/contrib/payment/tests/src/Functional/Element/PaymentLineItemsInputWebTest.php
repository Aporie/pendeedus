<?php

namespace Drupal\Tests\payment\Functional\Element;

use Drupal\payment\Payment;
use Drupal\payment\Tests\Generate;
use Drupal\Tests\BrowserTestBase;

/**
 * payment_line_items_input element web test.
 *
 * @group Payment
 */
class PaymentLineItemsInputWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment', 'payment_test');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Creates line item form data.
   *
   * @param string[] $names
   *   Line item machine names.
   *
   * @return array
   */
  protected function lineItemData(array $names) {
    $data = [];
    foreach ($names as $name) {
      $data += array(
        'line_item[line_items][' . $name . '][plugin_form][amount][amount]' => '10.0',
        'line_item[line_items][' . $name . '][plugin_form][description]' => 'foo',
        'line_item[line_items][' . $name . '][plugin_form][quantity]' => '1',
      );
    }

    return $data;
  }

  /**
   * Asserts the presence of the element's line item elements.
   */
  protected function assertLineItemElements(array $names) {
    foreach (array_keys($this->lineItemData($names)) as $input_name) {
      $this->assertFieldByName($input_name);
    }
  }

  /**
   * Asserts the presence of the element's add more elements..
   */
  protected function assertAddMore($present) {
    $elements = $this->xpath('//select[@name="line_item[add_more][type]"]');
    $this->assertEqual($present, isset($elements[0]));
    $elements = $this->xpath('//input[@id="edit-line-item-add-more-add"]');
    $this->assertEqual($present, isset($elements[0]));
  }

  /**
   * Tests the element.
   */
  public function testElement() {
    $state = \Drupal::state();
    $names = [];
    foreach (Generate::createPaymentLineItems() as $line_item) {
      $names[] = $line_item->getName();
    }
    $type = 'payment_basic';

    // Test the presence of default elements.
    $this->drupalGet('payment_test-element-payment-line-item');
    $this->assertLineItemElements($names);
    $this->assertAddMore(TRUE);

    // Add a line item through a regular submission.
    $this->drupalPostForm(NULL, array(
      'line_item[add_more][type]' => $type,
    ), t('Add and configure a new line item'));
    $this->assertLineItemElements(array_merge($names, array($type)));
    $this->assertAddMore(FALSE);

    // Delete a line item through a regular submission.
    $this->drupalPostForm(NULL, [], t('Delete'));
    array_shift($names);
    $this->assertLineItemElements($names);
    $elements = $this->xpath('//input[@name="line_item[line_items][' . $type . '][weight]"]');
    $this->assertFalse(isset($elements[0]));
    $this->assertAddMore(TRUE);

    // Change a line item's weight and test the element's value through a
    // regular submission.
    $name = 'line_item[line_items][' . reset($names) . '][weight]';
    $this->assertFieldByXPath('//select[@name="' . $name . '"]/option[@value="1" and @selected="selected"]');
    $this->drupalPostForm(NULL, array(
      // Change the first line item's weight to be the highest.
      $name => 5,
      // Set a description for the new element.
      'line_item[line_items][payment_basic][plugin_form][description]' => 'new',
    ), t('Submit'));
    $value = $state->get('payment_test_line_item_form_element');
    $this->assertTrue(is_array($value));
    // One item was deleted, one added, again resulting in 3.
    $this->assertCount(3, $value);
    $line_items = [];
    foreach ($value as $line_item_data) {
      $this->assertTrue(isset($line_item_data['plugin_configuration']));
      $this->assertTrue(is_array($line_item_data['plugin_configuration']));
      $this->assertTrue(isset($line_item_data['plugin_id']));
      $line_items[] = Payment::lineItemManager()->createInstance($line_item_data['plugin_id'], $line_item_data['plugin_configuration']);
    }
    // Check that the first line item is now the last.
    $this->assertEqual(end($line_items)->getName(), reset($names));
  }
}
