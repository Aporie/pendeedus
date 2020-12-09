<?php

namespace Drupal\Tests\payment\Functional\Controller;

use Drupal\payment\Payment;
use Drupal\payment\Tests\Generate;
use Drupal\Tests\BrowserTestBase;

/**
 * Payment UI.
 *
 * @group Payment
 */
class PaymentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment', 'payment_test', 'block', 'views');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the payment UI.
   */
  public function testPaymentUi() {
    $this->drupalPlaceBlock('local_tasks_block');
    /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface $payment_method */
    $payment_method = Payment::methodManager()->createInstance('payment_test');
    // Create just enough payments for three pages
    $count_payments = 50 * 2 + 1;
    foreach (range(0, $count_payments) as $i) {
      $payment = Generate::createPayment(2, $payment_method);
      $payment->save();
      $payment = \Drupal\payment\Entity\Payment::load($payment->id());
    }

    // View the administrative listing.
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages')));
    $this->drupalGet('admin/content');
    $this->assertResponse('200');
    $this->assertNoLinkByHref('admin/content/payment');
    $this->drupalGet('admin/content/payment');
    $this->assertResponse('403');
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages', 'payment.payment.view.any')));
    $this->drupalGet('admin/content');
    $this->clickLink(t('Payments'));
    $this->assertResponse('200');
    $this->assertTitle(t('Payments | Drupal'));
    $this->assertText(t('Last updated'));
    $this->assertText(t('Payment method'));
    $this->assertText(t('Enter a comma separated list of user names.'));
    $this->assertText(t('EUR 24.20'));
    $this->assertText($payment_method->getPluginLabel());
    $count_pages = ceil($count_payments / 50);
    if ($count_pages) {
      foreach (range(1, $count_pages - 1) as $page) {
        $this->assertLinkByHref('&page=' . $page);
      }
      $this->assertNoLinkByHref('&page=' . ($page + 1));
    }
    $this->assertLinkByHref('payment/1');
    // @todo The following code does not work, as it results in the following
    // failure if this test is run on Drush' built-in server:
    // @code
    // Expected &#039;http://local.dev:8080/admin/content/payment%3Fchanged_after%3D%26changed_before%3D%26%3DApply%26page%3D1&#039;
    //
    // matches current URL
    //
    // (http://local.dev:8080/admin/content/payment?q=admin%2Fcontent%2Fpayment&amp;changed_after=&amp;changed_before=&amp;=Apply&amp;page=1).
    //
    // Value
    //
    // &#039;http://local.dev:8080/admin/content/payment?q=admin/content/payment&amp;changed_after=&amp;changed_before=&amp;=Apply&amp;page=1&#039;
    //
    // is equal to value
    //
    // &#039;http://local.dev:8080/admin/content/payment?changed_after=&amp;changed_before=&amp;=Apply&amp;page=1&#039;.
    // @endcode
    //
    // @code
    // $this->assertUrl('admin/content/payment', [
    // 'changed_after' => '',
    // 'changed_before' => '',
    // 'Apply' => '',
    // 'page' => 1,
    // ]);
    // @endcode
    $this->drupalLogout();

    // View the payment.
    $path = 'payment/' . $payment->id();
    $this->drupalGet($path);
    $this->assertResponse('403');
    $this->drupalLogin($this->drupalCreateUser(array('payment.payment.view.any')));
    $this->drupalGet($path);
    $this->assertResponse('200');
    $this->assertText(t('Payment method'));
    $this->assertText(t('Status'));

    // Delete a payment.
    $path = 'payment/' . $payment->id() . '/delete';
    $this->drupalGet($path);
    $this->assertResponse('403');
    $this->drupalLogin($this->drupalCreateUser(array('payment.payment.delete.any', 'payment.payment.view.any')));
    $this->drupalGet($path);
    $this->assertResponse('200');
    $this->clickLink(t('Cancel'));
    $this->assertUrl('payment/' . $payment->id());
    $this->drupalGet($path);
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertResponse('200');
    $this->assertFalse((bool) \Drupal::entityTypeManager()->getStorage('payment')->loadUnchanged($payment->id()));
  }
}
