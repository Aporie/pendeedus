<?php

namespace Drupal\Tests\payment_reference\Functional\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\payment\Payment;
use Drupal\payment\Tests\Generate;
use Drupal\payment_reference\PaymentReference;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * \Drupal\payment_reference\Plugin\Field\FieldType\PaymentReference web test.
 *
 * @group Payment Reference Field
 */
class PaymentReferenceWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('field_ui', 'payment', 'payment_reference');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    /** @var \Drupal\currency\ConfigImporterInterface $config_importer */
    $config_importer = \Drupal::service('currency.config_importer');
    $config_importer->importCurrency('EUR');
  }

  /**
   * Tests the field.
   */
  public function testField() {
    // Create the field and field instance.
    $field_name = strtolower($this->randomMachineName());
    FieldStorageConfig::create(array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'entity_type' => 'user',
      'field_name' => $field_name,
      'type' => 'payment_reference',
    ))->save();

    FieldConfig::create(array(
      'bundle' => 'user',
      'entity_type' => 'user',
      'field_name' => $field_name,
      'settings' => array(
        'currency_code' => 'EUR',
        'line_items_data' => [],
      ),
    ))->save();

    $payment = Generate::createPayment(mt_rand());
    $payment->setPaymentStatus(Payment::statusManager()->createInstance('payment_success'));
    $payment->save();
    PaymentReference::queue()->save('user.' . $field_name, $payment->id());
    $this->assertEqual(PaymentReference::queue()->loadPaymentIds('user.' . $field_name, $payment->getOwnerId()), array($payment->id()));

    // Set a field value on an entity and test getting it.
    /** @var \Drupal\user\UserInterface $user */
    $user = User::create(array(
      'name' => $this->randomString(),
    ));

    $user->get($field_name)->appendItem($payment->id());
    $this->assertEqual($user->get($field_name)->first()->entity->id(), $payment->id());

    // Save the entity, load it from storage and test getting the field value.
    $user->save();
    $user = User::load($user->id());
    $this->assertEqual($user->{$field_name}[0]->target_id, $payment->id());
    $this->assertEqual(PaymentReference::queue()->loadPaymentIds('user.' . $field_name, $payment->getOwnerId()), []);
  }

  /**
   * Tests creating the field through the administrative user interface.
   */
  public function testFieldCreation() {
    $field_id = strtolower($this->randomMachineName());
    $field_label = $this->randomMachineName();
    $description = $this->randomMachineName();
    $quantity = mt_rand();
    $currency_code = 'EUR';
    $amount = '12.34';
    $user = $this->drupalCreateUser(array('administer user fields'));
    $this->drupalLogin($user);
    $this->drupalPostForm('admin/config/people/accounts/fields/add-field', array(
      'label' => $field_label,
      'field_name' => $field_id,
      'new_storage_type' => 'payment_reference',
    ), t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->drupalPostForm(NULL, array(
      'settings[line_items][add_more][type]' => 'payment_basic',
    ), t('Add and configure a new line item'));
    $this->drupalPostForm(NULL, array(
      'settings[currency_code]' => $currency_code,
      'settings[line_items][line_items][payment_basic][plugin_form][amount][amount]' => $amount,
      'settings[line_items][line_items][payment_basic][plugin_form][amount][currency_code]' => $currency_code,
      'settings[line_items][line_items][payment_basic][plugin_form][description]' => $description,
      'settings[line_items][line_items][payment_basic][plugin_form][quantity]' => $quantity,
    ), t('Save settings'));
    $this->assertResponse(200);

    // Re-load the page and test that the values are picked up.
    $this->drupalGet('admin/config/people/accounts/fields/user.user.field_' . $field_id);
    $this->assertFieldByName('settings[line_items][line_items][payment_basic][plugin_form][amount][currency_code]', $currency_code);
    $this->assertFieldByName('settings[line_items][line_items][payment_basic][plugin_form][amount][amount]', $amount);
    $this->assertFieldByName('settings[line_items][line_items][payment_basic][plugin_form][description]', $description);
    $this->assertFieldByName('settings[line_items][line_items][payment_basic][plugin_form][quantity]', $quantity);
  }
}
