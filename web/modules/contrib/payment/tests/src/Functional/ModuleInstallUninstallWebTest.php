<?php

namespace Drupal\Tests\payment\Functional;

use Drupal\payment\Entity\PaymentMethodConfiguration;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation and uninstallation.
 *
 * @group Payment
 */
class ModuleInstallUninstallWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('payment');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test installation and uninstallation.
   */
  public function testInstallationAndUninstallation() {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_handler = \Drupal::moduleHandler();
    $this->assertTrue($module_handler->moduleExists('payment'));

    // Test default configuration.
    $names = array('collect_on_delivery', 'no_payment_required');
    foreach ($names as $name) {
      $payment_method = PaymentMethodConfiguration::load($name);
      $this->assertTrue($payment_method instanceof PaymentMethodConfigurationInterface);
    }

    $module_installer->uninstall(array('payment'));
    $this->assertFalse($module_handler->moduleExists('payment'));
  }
}
