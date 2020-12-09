<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Controller\SelectPaymentMethodConfiguration;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\SelectPaymentMethodConfiguration
 *
 * @group Payment
 */
class SelectPaymentMethodConfigurationTest extends UnitTestCase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The payment method configuration access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationAccessControlHandler;

  /**
   * The payment method configuration manager.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\SelectPaymentMethodConfiguration
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->paymentMethodConfigurationAccessControlHandler = $this->createMock(EntityAccessControlHandlerInterface::class);

    $this->paymentMethodConfigurationManager = $this->createMock(PaymentMethodConfigurationManagerInterface::class);

    $this->sut = new SelectPaymentMethodConfiguration($this->paymentMethodConfigurationAccessControlHandler, $this->paymentMethodConfigurationManager, $this->currentUser);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->atLeastOnce())
      ->method('getAccessControlHandler')
      ->with('payment_method_configuration')
      ->willReturn($this->paymentMethodConfigurationAccessControlHandler);

    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['current_user', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->currentUser],
      ['entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_type_manager],
      ['plugin.manager.payment.method_configuration', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodConfigurationManager],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = SelectPaymentMethodConfiguration::create($container);
    $this->assertInstanceOf(SelectPaymentMethodConfiguration::class, $sut);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $definitions = [
      'payment_unavailable' => [],
      'foo' => [
        'description' => $this->randomMachineName(),
        'label' => $this->randomMachineName(),
      ],
      'bar' => [
        'description' => $this->randomMachineName(),
        'label' => $this->randomMachineName(),
      ],
    ];
    $this->paymentMethodConfigurationManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->paymentMethodConfigurationAccessControlHandler->expects($this->any())
      ->method('createAccess')
      ->willReturn(TRUE);

    $this->sut->execute();
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $definitions = [
      'payment_unavailable' => [],
      'foo' => [
        'description' => $this->randomMachineName(),
        'label' => $this->randomMachineName(),
      ],
      'bar' => [
        'description' => $this->randomMachineName(),
        'label' => $this->randomMachineName(),
      ],
    ];
    $this->paymentMethodConfigurationManager->expects($this->exactly(2))
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->paymentMethodConfigurationAccessControlHandler->expects($this->at(0))
      ->method('createAccess')
      ->with('foo', $this->currentUser, [], TRUE)
      ->willReturn(AccessResult::allowed());
    $this->paymentMethodConfigurationAccessControlHandler->expects($this->at(1))
      ->method('createAccess')
      ->with('foo', $this->currentUser, [], TRUE)
      ->willReturn(AccessResult::forbidden());
    $this->paymentMethodConfigurationAccessControlHandler->expects($this->at(2))
      ->method('createAccess')
      ->with('bar', $this->currentUser, [], TRUE)
      ->willReturn(AccessResult::forbidden());

    $this->assertTrue($this->sut->access()->isAllowed());
    $this->assertFalse($this->sut->access()->isAllowed());
  }

}
