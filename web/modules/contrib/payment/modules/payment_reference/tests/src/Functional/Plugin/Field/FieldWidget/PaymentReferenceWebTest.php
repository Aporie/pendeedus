<?php

namespace Drupal\Tests\payment_reference\Functional\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\payment\Tests\Generate;
use Drupal\Tests\BrowserTestBase;

/**
 * \Drupal\payment_reference\Plugin\Field\FieldWidget\PaymentReference web test.
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
   * Tests the widget.
   */
  public function testWidget() {
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
        'line_items' => [],
      ),
    ))->save();

    \Drupal::service('entity_display.repository')->getFormDisplay('user', 'user', 'default')
      ->setComponent($field_name, [])
      ->save();

    $user = $this->drupalCreateUser(array('payment.payment.view.own'));
    $this->drupalLogin($user);

    $payment_method = Generate::createPaymentMethodConfiguration(mt_rand(), 'payment_basic');
    $payment_method->setPluginConfiguration(array(
      'brand_label' => $this->randomMachineName(),
      'execute_status_id' => 'payment_success',
      'message_text' => $this->randomMachineName(),
    ));
    $payment_method->save();

    // Test the widget when editing an entity.
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->drupalPostForm(NULL, [], t('Re-check available payments'));
    $this->drupalPostForm(NULL, [], t('Pay'));
    $this->assertNoFieldByXPath('//input[@value="Pay"]');
    $this->assertLinkByHref('payment/1');
  }
}
