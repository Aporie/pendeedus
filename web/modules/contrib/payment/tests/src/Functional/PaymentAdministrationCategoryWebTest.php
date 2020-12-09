<?php

namespace Drupal\Tests\payment\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Payment category in the administration UI.
 *
 * @group Payment
 */
class PaymentAdministrationCategoryWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests administrative overview.
   */
  public function testOverview() {
    $this->drupalGet('admin/config/services');
    $this->assertNoLink('Payment');
    $this->drupalGet('admin/config/services/payment');
    $this->assertResponse('403');
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages')));
    $this->drupalGet('admin/config');
    $this->drupalGet('admin/config/services');
    $this->assertLink('Payment');
    $this->drupalGet('admin/config/services/payment');
    $this->assertResponse('200');
  }
}
