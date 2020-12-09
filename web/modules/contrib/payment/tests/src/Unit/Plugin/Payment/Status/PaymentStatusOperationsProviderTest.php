<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Status;

use Drupal\payment\Plugin\Payment\Status\PaymentStatusOperationsProvider;
use Drupal\Tests\plugin\Unit\PluginType\DefaultPluginTypeOperationsProviderTest;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Status\PaymentStatusOperationsProvider
 *
 * @group Plugin
 */
class PaymentStatusOperationsProviderTest extends DefaultPluginTypeOperationsProviderTest {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusOperationsProvider
   */
  protected $sut;

  public function setUp(): void {
    parent::setUp();

    $this->sut = new PaymentStatusOperationsProvider($this->stringTranslation);
  }

}
