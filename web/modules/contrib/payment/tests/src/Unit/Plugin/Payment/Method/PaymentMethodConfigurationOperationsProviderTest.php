<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Method;

use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodConfigurationOperationsProvider;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Method\PaymentMethodConfigurationOperationsProvider
 *
 * @group Payment
 */
class PaymentMethodConfigurationOperationsProviderTest extends UnitTestCase {

  /**
   * The payment method configuration list builder.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationListBuilder;

  /**
   * The payment method configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationStorage;

  /**
   * The class under test
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodConfigurationOperationsProvider|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sut;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $redirectDestination;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentMethodConfigurationListBuilder = $this->createMock(EntityListBuilderInterface::class);

    $this->paymentMethodConfigurationStorage = $this->createMock(EntityStorageInterface::class);

    $this->redirectDestination = $this->createMock(RedirectDestinationInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = $this->getMockBuilder(PaymentMethodConfigurationOperationsProvider::class)
      ->setConstructorArgs([$this->stringTranslation, $this->redirectDestination, $this->paymentMethodConfigurationStorage, $this->paymentMethodConfigurationListBuilder])
      ->getMockForAbstractClass();
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getListBuilder')
      ->with('payment_method_configuration')
      ->willReturn($this->paymentMethodConfigurationListBuilder);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('payment_method_configuration')
      ->willReturn($this->paymentMethodConfigurationStorage);

    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_type_manager),
      array('redirect.destination', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->redirectDestination),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodConfigurationOperationsProvider $class_name */
    $class_name = get_class($this->sut);

    $sut = $class_name::create($container);
    $this->assertInstanceOf(PaymentMethodConfigurationOperationsProvider::class, $sut);
  }

  /**
   * @covers ::getOperations
   */
  public function testGetOperations() {
    $list_builder_operations = [
      'edit' => [
        'title' => 'Edit configuration',
        ],
      'delete' => [
        'title' => 'Delete configuration',
        ],
      'enable' => [
        'title' => 'Enable configuration',
        ],
      'disable' => [
        'title' => 'Disable configuration',
      ],
      'foo' => [],
    ];

    $destination = $this->randomMachineName();

    $this->redirectDestination->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn($destination);

    $plugin_id = $this->randomMachineName();

    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);

    $this->sut->expects($this->once())
      ->method('getPaymentMethodConfiguration')
      ->with($plugin_id)
      ->willReturn($payment_method_configuration);

    $this->paymentMethodConfigurationListBuilder->expects($this->once())
      ->method('getOperations')
      ->with($payment_method_configuration)
      ->willReturn($list_builder_operations);

    $expected_operations = $list_builder_operations;
    unset($expected_operations['foo']);
    foreach ($expected_operations as $name => $expected_operation) {
      $expected_operations[$name]['title'] = $list_builder_operations[$name]['title'];
      $expected_operations[$name]['query']['destination'] = $destination;
    }
    $this->assertEquals($expected_operations, $this->sut->getOperations($plugin_id));
  }

}
