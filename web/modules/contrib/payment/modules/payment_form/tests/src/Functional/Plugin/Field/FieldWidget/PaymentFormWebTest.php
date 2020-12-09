<?php

namespace Drupal\Tests\payment_form\Functional\Plugin\Field\FieldWidget;

use Drupal\Tests\BrowserTestBase;

/**
 * \Drupal\payment_form\Plugin\Field\FieldWidget\PaymentForm web test.
 *
 * @group Payment Form Field
 */
class PaymentFormWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'payment', 'payment_form'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the widget.
   */
  public function testWidget() {
    /** @var \Drupal\currency\ConfigImporterInterface $config_importer */
    $config_importer = \Drupal::service('currency.config_importer');
    $config_importer->importCurrency('EUR');

    $user = $this->drupalCreateUser(['administer user fields']);
    $this->drupalLogin($user);

    // Test the widget when setting a default field value.
    $field_name = strtolower($this->randomMachineName());
    $this->drupalPostForm('admin/config/people/accounts/fields/add-field', [
      'label' => $this->randomString(),
      'field_name' => $field_name,
      'new_storage_type' => 'payment_form',
    ], t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->drupalPostForm(NULL, [], t('Add and configure a new line item'));
    $this->drupalPostForm(NULL, [
      'default_value_input[field_' . $field_name . '][line_items][line_items][payment_basic][plugin_form][description]' => $this->randomString(),
    ], t('Save settings'));
    // Get all payment_form fields.
    $field_names = \Drupal::entityQuery('field_storage_config')
      ->condition('type', 'payment_form')
      ->execute();
    $this->assertTrue(in_array('user.field_' . $field_name, $field_names));

    // Test the widget when creating an entity.
    $this->drupalPostForm('user/' . $user->id() . '/edit', [
      'field_' . $field_name . '[line_items][add_more][type]' => 'payment_basic',
    ], t('Add and configure a new line item'));
    $description = $this->randomString();
    $this->drupalPostForm(NULL, [
      'field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][amount][amount]' => '9,87',
      'field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][amount][currency_code]' => 'EUR',
      'field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][description]' => $description,
      'field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][quantity]' => 37,
    ], t('Save'));

    // Test whether the widget displays field values.
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertFieldByName('field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][amount][amount]', '9.87');
    $this->assertFieldByName('field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][amount][currency_code]', 'EUR');
    $this->assertFieldByName('field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][description]', $description);
    $this->assertFieldByName('field_' . $field_name . '[line_items][line_items][payment_basic][plugin_form][quantity]', 37);
  }
}
