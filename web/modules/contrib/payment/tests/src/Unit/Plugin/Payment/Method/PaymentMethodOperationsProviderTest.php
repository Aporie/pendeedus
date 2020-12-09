<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Method;

use Drupal\payment\Plugin\Payment\Method\PaymentMethodOperationsProvider;
use Drupal\Tests\plugin\Unit\PluginType\DefaultPluginTypeOperationsProviderTest;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Method\PaymentMethodOperationsProvider
 *
 * @group Plugin
 */
class PaymentMethodOperationsProviderTest extends DefaultPluginTypeOperationsProviderTest {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodOperationsProvider
   */
  protected $sut;

  public function setUp(): void {
    parent::setUp();

    $this->sut = new PaymentMethodOperationsProvider($this->stringTranslation);
  }

}
