<?php

namespace Drupal\Tests\payment\Unit;

use Drupal\payment\Permissions;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Permissions
 *
 * @group Payment
 */
class PermissionsTest extends UnitTestCase {

  /**
   * The payment method configuration manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Permissions.
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentMethodConfigurationManager = $this->createMock(PaymentMethodManagerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new Permissions($this->stringTranslation, $this->paymentMethodConfigurationManager);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('plugin.manager.payment.method_configuration', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodConfigurationManager),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = Permissions::create($container);
    $this->assertInstanceOf(Permissions::class, $sut);
  }

  /**
   * @covers ::getPermissions
   */
  public function testInvoke() {
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

    $permissions = $this->sut->getPermissions();
    $this->assertIsArray($permissions);
    foreach ($permissions as $permission) {
      $this->assertIsArray($permission);
      $this->assertArrayHasKey('title', $permission);
    }
    $this->arrayHasKey('payment.payment_method_configuration.create.'. $payment_method_configuration_plugin_id, $permissions);
  }
}
