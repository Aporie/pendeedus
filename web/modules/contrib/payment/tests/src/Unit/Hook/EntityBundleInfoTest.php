<?php

namespace Drupal\Tests\payment\Unit\Hook;

use Drupal\payment\Hook\EntityBundleInfo;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Hook\EntityBundleInfo
 *
 * @group Payment
 */
class EntityBundleInfoTest extends UnitTestCase {

  /**
   * The payment method configuration manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The payment type manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentTypeManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Hook\EntityBundleInfo
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentMethodConfigurationManager = $this->createMock(PaymentMethodConfigurationManagerInterface::class);

    $this->paymentTypeManager = $this->createMock(PaymentTypeManagerInterface::class);

    $this->sut = new EntityBundleInfo($this->paymentTypeManager, $this->paymentMethodConfigurationManager);
  }

  /**
   * @covers ::invoke
   */
  public function testInvoke() {
    $payment_type_plugin_id = $this->randomMachineName();
    $payment_type_label = $this->randomMachineName();
    $payment_type_definitions = array(
      $payment_type_plugin_id => array(
        'label' => $payment_type_label
      ),
    );
    $this->paymentTypeManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($payment_type_definitions);

    $payment_method_configuration_plugin_id = $this->randomMachineName();
    $payment_method_configuration_label = $this->randomMachineName();
    $payment_method_configuration_definitions = array(
      $payment_method_configuration_plugin_id => array(
        'label' => $payment_method_configuration_label
      ),
    );
    $this->paymentMethodConfigurationManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($payment_method_configuration_definitions);

    $entity_types = array(
      'payment' => $payment_type_definitions,
      'payment_method_configuration' => $payment_method_configuration_definitions,
    );
    $entity_types_bundles_info = $this->sut->invoke();
    $this->assertSame(count($entity_types), count($entity_types_bundles_info));
    foreach ($entity_types as $entity_type => $plugin_definitions) {
      $entity_type_bundles_info = $entity_types_bundles_info[$entity_type];
      $this->assertIsArray($entity_type_bundles_info);
      foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
        $this->assertArrayHasKey('label', $entity_type_bundles_info[$plugin_id]);
        $this->assertSame($plugin_definition['label'], $entity_type_bundles_info[$plugin_id]['label']);
      }
    }
  }
}
