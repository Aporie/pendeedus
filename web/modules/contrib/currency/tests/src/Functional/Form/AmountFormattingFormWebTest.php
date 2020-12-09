<?php

namespace Drupal\Tests\currency\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * \Drupal\currency\Form\AmountFormattingForm web test.
 *
 * @group Currency
 */
class AmountFormattingFormWebTest extends BrowserTestBase {

  public static $modules = array('currency');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests listing().
   */
  function testListing() {
    $account = $this->drupalCreateUser(array('access administration pages'));
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/regional/currency-formatting');
    $this->assertResponse('403');

    $account = $this->drupalCreateUser(array('currency.amount_formatting.administer'));
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/regional/currency-formatting');
    $this->assertResponse('200');

    $this->assertFieldChecked('edit-default-plugin-id-currency-basic');
    $this->drupalPostForm(NULL, array(), t('Save configuration'));
  }
}
