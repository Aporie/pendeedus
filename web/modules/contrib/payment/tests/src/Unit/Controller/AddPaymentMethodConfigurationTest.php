<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\payment\Controller\AddPaymentMethodConfiguration;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\payment\Controller\AddPaymentMethodConfiguration
 *
 * @group Payment
 */
class AddPaymentMethodConfigurationTest extends UnitTestCase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The payment method configuration plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestStack;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\AddPaymentMethodConfiguration
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->entityFormBuilder = $this->createMock(EntityFormBuilderInterface::class);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->paymentMethodConfigurationManager = $this->createMock(PaymentMethodConfigurationManagerInterface::class);

    $this->requestStack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new AddPaymentMethodConfiguration($this->requestStack, $this->stringTranslation, $this->entityTypeManager, $this->paymentMethodConfigurationManager, $this->entityFormBuilder, $this->currentUser);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['current_user', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->currentUser],
      ['entity.form_builder', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->entityFormBuilder],
      ['entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->entityTypeManager],
      ['plugin.manager.payment.method_configuration', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodConfigurationManager],
      ['request_stack', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->requestStack],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = AddPaymentMethodConfiguration::create($container);
    $this->assertInstanceOf(AddPaymentMethodConfiguration::class, $sut);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $plugin_id = $this->randomMachineName();

    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);

    $storage_controller = $this->createMock(EntityStorageInterface::class);
    $storage_controller->expects($this->once())
      ->method('create')
      ->willReturn($payment_method_configuration);

    $form = $this->createMock(EntityFormInterface::class);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('payment_method_configuration')
      ->willReturn($storage_controller);

    $this->entityFormBuilder->expects($this->once())
      ->method('getForm')
      ->with($payment_method_configuration, 'default')
      ->willReturn($form);

    $this->sut->execute($plugin_id);
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $plugin_id = $this->randomMachineName();
    $request = new Request();
    $request->attributes->set('plugin_id', $plugin_id);

    $this->requestStack->expects($this->atLeastOnce())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $access_controller = $this->createMock(EntityAccessControlHandlerInterface::class);
    $access_controller->expects($this->at(0))
      ->method('createAccess')
      ->with($plugin_id, $this->currentUser, [], TRUE)
      ->willReturn(AccessResult::allowed());
    $access_controller->expects($this->at(1))
      ->method('createAccess')
      ->with($plugin_id, $this->currentUser, [], TRUE)
      ->willReturn(AccessResult::forbidden());

    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getAccessControlHandler')
      ->with('payment_method_configuration')
      ->willReturn($access_controller);

    $this->assertTrue($this->sut->access($request)->isAllowed());
    $this->assertFalse($this->sut->access($request)->isAllowed());
  }

  /**
   * @covers ::title
   */
  public function testTitle() {
    $plugin_id = $this->randomMachineName();

    $this->paymentMethodConfigurationManager->expects($this->atLeastOnce())
      ->method('getDefinition')
      ->with($plugin_id)
      ->willReturn([
        'label' => $this->randomMachineName(),
      ]);

    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->title($plugin_id));
  }

}
