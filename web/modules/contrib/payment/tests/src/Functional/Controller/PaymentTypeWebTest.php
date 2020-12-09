<?php

namespace Drupal\Tests\payment\Functional\Controller;

use Drupal\Tests\BrowserTestBase;

/**
 * Payment type UI.
 *
 * @group Payment
 */
class PaymentTypeWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('field_ui', 'payment', 'payment_test');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests administrative overview.
   */
  public function testOverview() {
    $admin = $this->drupalCreateUser(array('access administration pages'));
    $admin_payment_type = $this->drupalCreateUser(array('access administration pages', 'payment.payment_type.administer'));

    // Test the plugin listing.
    $this->drupalGet('admin/config/services/payment');
    $this->assertNoLink('Payment types');
    $this->drupalGet('admin/config/services/payment/type');
    $this->assertResponse('403');
    $this->drupalLogin($admin);
    $this->drupalGet('admin/config/services/payment');
    $this->assertNoLink('Payment types');
    $this->drupalGet('admin/config/services/payment/type');
    $this->assertResponse('403');
    $this->drupalLogin($admin_payment_type);
    $this->drupalGet('admin/config/services/payment');
    $this->assertLink('Payment types');
    $this->drupalGet('admin/config/services/payment/type');
    $this->assertResponse('200');
    $this->assertText(t('Test type'));

    // Test the dummy payment type route.
    $this->drupalGet('admin/config/services/payment/type/payment_test');
    $this->assertResponse('200');

    // Test field operations.
    $this->drupalLogout();
    $links = array(
      'administer payment display' => t('Manage display'),
      'administer payment fields' => t('Manage fields'),
      'administer payment form display' => t('Manage form display'),
    );
    $path = 'admin/config/services/payment/type';
    foreach ($links as $permission => $text) {
      $this->drupalLogin($admin_payment_type);
      $this->drupalGet($path);
      $this->assertResponse('200');
      $this->assertNoLink($text);
      $this->drupalLogin($this->drupalCreateUser(array($permission, 'payment.payment_type.administer')));
      $this->drupalGet($path);
      $this->clickLink($text);
      $this->assertResponse('200');
      $this->assertTitle($text . ' | Drupal');
    }
  }
}
