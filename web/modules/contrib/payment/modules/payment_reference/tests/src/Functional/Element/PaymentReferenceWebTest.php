<?php

namespace Drupal\Tests\payment_reference\Functional\Element;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\payment\Entity\Payment;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * payment_reference element web test.
 *
 * @group Payment Reference Field
 */
class PaymentReferenceWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment', 'payment_reference', 'payment_reference_test', 'payment_test', 'text');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the element.
   */
  public function testElement() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    /** @var \Drupal\currency\ConfigImporterInterface $config_importer */
    $config_importer = \Drupal::service('currency.config_importer');
    $config_importer->importCurrency('EUR');

    // Create the payment reference field and field instance.
    $payment_reference_field_name = 'foobarbaz';
    FieldStorageConfig::create(array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'entity_type' => 'user',
      'field_name' => $payment_reference_field_name,
      'type' => 'payment_reference',
    ))->save();
    FieldConfig::create(array(
      'bundle' => 'user',
      'entity_type' => 'user',
      'field_name' => $payment_reference_field_name,
      'settings' => array(
        'currency_code' => 'EUR',
        'line_items_data' => [],
      ),
    ))->save();

    // Create a field on the payment entity type.
    $payment_field_name = 'quxfoobar';
    FieldStorageConfig::create(array(
      'cardinality' => 1,
      'entity_type' => 'payment',
      'field_name' => $payment_field_name,
      'type' => 'text',
    ))->save();
    FieldConfig::create(array(
      'bundle' => 'payment_reference',
      'entity_type' => 'payment',
      'field_name' => $payment_field_name,
      'required' => TRUE,
    ))->save();
    \Drupal::service('entity_display.repository')->getFormDisplay('payment', 'payment_reference', 'default')
      ->setComponent($payment_field_name, array(
        'type' => 'text_textfield',
      ))
      ->save();

    $state = \Drupal::state();
    $path = 'payment_reference_test-element-payment_reference';

    // Test without selecting a payment method.
    $this->drupalGet($path);
    $this->drupalPostForm(NULL, [], t('Pay'));
    $this->assertText('quxfoobar field is required');
    $value = $state->get('payment_reference_test_payment_reference_element');
    $this->assertNull($value);

    // Test with a payment method that returns no execution completion response.
    $text_field_value = $this->randomMachineName();
    $this->drupalPostForm($path, array(
      'payment_reference[container][payment_form][payment_method][container][select][container][plugin_id]' => 'payment_test_no_response',
      'payment_reference[container][payment_form][quxfoobar][0][value]' => $text_field_value,
    ), t('Choose'));
    $this->drupalPostForm(NULL, [], t('Pay'));
    $this->drupalPostForm(NULL, [], t('Submit'));
    $payment_id = $state->get('payment_reference_test_payment_reference_element');
    $this->assertTrue(is_int($payment_id));
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = Payment::load($payment_id);
    $this->assertTrue($payment instanceof PaymentInterface);
    $this->assertEqual($payment->get('quxfoobar')[0]->get('value')->getValue(), $text_field_value);

    // Test with a payment method that returns an execution completion response.
    $text_field_value = $this->randomMachineName();
    $this->drupalPostForm($path, array(
      'payment_reference[container][payment_form][payment_method][container][select][container][plugin_id]' => 'payment_test_response',
      'payment_reference[container][payment_form][quxfoobar][0][value]' => $text_field_value,
    ), t('Choose'));
    $this->drupalPostForm(NULL, [], t('Pay'));
    $this->clickLink(t('Complete payment (opens in a new window).'));
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = Payment::loadMultiple()[2];
    $this->assertEqual($payment->getPaymentStatus()->getPluginId(), 'payment_success');
    $this->assertEqual($payment->get('quxfoobar')[0]->get('value')->getValue(), $text_field_value);
  }

}
