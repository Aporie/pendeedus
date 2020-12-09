<?php

namespace Drupal\Tests\payment_form\Functional\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\payment\Tests\Generate;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * \Drupal\payment_form\Plugin\Field\FieldType\PaymentForm.
 *
 * @group Payment Form Field
 */
class PaymentFormWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['payment', 'payment_form'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the field.
   */
  public function testField() {
    // Create the field and field instance.
    $field_name = strtolower($this->randomMachineName());
    FieldStorageConfig::create([
      'cardinality' => FieldStorageConfigInterface::CARDINALITY_UNLIMITED,
      'entity_type' => 'user',
      'field_name' => $field_name,
      'type' => 'payment_form',
    ])->save();

    FieldConfig::create([
      'bundle' => 'user',
      'entity_type' => 'user',
      'field_name' => $field_name,
      'settings' => [
        'currency_code' => 'EUR',
      ],
    ])->save();

    // Set a field value on an entity and test getting it.
    $user = User::create([
      'name' => $this->randomString(),
    ]);
    foreach (Generate::createPaymentLineItems() as $line_item) {
      $user->get($field_name)->appendItem([
        'plugin_id' => $line_item->getPluginId(),
        'plugin_configuration' => $line_item->getConfiguration(),
      ]);
    }
    $this->assertFieldValue($user, $field_name);

    // Save the entity, load it from storage and test getting the field value.
    $user->save();
    $user = User::load($user->id());
    $this->assertFieldValue($user, $field_name);
  }

  /**
   * Asserts a correct field value.
   */
  protected function assertFieldValue(EntityInterface $entity, $field_name) {
    $field = $entity->{$field_name};
    foreach (Generate::createPaymentLineItems() as $i => $line_item) {
      $this->assertTrue(is_string($field[$i]->plugin_id));
      $this->assertTrue(is_array($field[$i]->plugin_configuration));
    }
  }
}
