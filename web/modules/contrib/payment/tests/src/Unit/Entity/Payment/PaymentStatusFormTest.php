<?php

namespace Drupal\Tests\payment\Unit\Entity\Payment;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\payment\Entity\Payment\PaymentStatusForm;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodUpdatePaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\payment\Entity\Payment\PaymentStatusForm
 *
 * @group Payment
 */
class PaymentStatusFormTest extends UnitTestCase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\Payment|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The payment status plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusManager;

  /**
   * The plugin selector.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginSelector;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginSelectorManager;

  /**
   * The payment status plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $paymentStatusPluginType;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The entity type bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityRepository;

  /**
   * The form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formDisplay;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Entity\Payment\PaymentStatusForm
   */
  protected $sut;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

    $this->pluginSelector = $this->createMock(PluginSelectorInterface::class);

    $this->pluginSelectorManager = $this->createMock(PluginSelectorManagerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->paymentStatusPluginType = $this->prophesize(PluginTypeInterface::class);

    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

    $this->payment = $this->createMock(PaymentInterface::class);

    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class)->reveal();
    $this->time = $this->prophesize(TimeInterface::class)->reveal();
    $this->formDisplay = $this->prophesize(EntityFormDisplayInterface::class)->reveal();

    $this->sut = new PaymentStatusForm($this->entityRepository, $this->entityTypeBundleInfo, $this->time);
    $this->sut->setStringTranslation($this->stringTranslation);
    $this->sut->setEntity($this->payment);
    $this->sut->setPaymentStatusPluginType($this->paymentStatusPluginType->reveal());
    $this->sut->setPluginSelectorManager($this->pluginSelectorManager);
    $this->sut->setCurrentUser($this->currentUser);
  }

  /**
   * @covers ::form
   * @covers ::getPluginSelector
   */
  public function testForm() {
    $form = [];
    $form_state = new FormState();

    $settable_payment_status_ids = array($this->randomMachineName());

    $language = $this->createMock(LanguageInterface::class);

    $payment_method = $this->createMock(PaymentStatusFormUnitTestDummyPaymentMethodUpdateStatusInterface::class);
    $payment_method->expects($this->once())
      ->method('getSettablePaymentStatuses')
      ->with($this->currentUser)
      ->willReturn($settable_payment_status_ids);

    $this->payment->expects($this->atLeastOnce())
      ->method('getPaymentMethod')
      ->willReturn($payment_method);
    $this->payment->expects($this->any())
      ->method('language')
      ->willReturn($language);
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $this->payment->expects($this->any())
      ->method('getEntityType')
      ->willReturn($entity_type);

    $this->pluginSelectorManager->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->pluginSelector);

    $plugin_selector_form = [
      'foo' => $this->randomMachineName(),
    ];

    $form = [
      'langcode' => [],
    ];
    $form_state = new FormState();
    $this->sut->setFormDisplay($this->formDisplay, $form_state);

    $this->pluginSelector->expects($this->atLeastOnce())
      ->method('buildSelectorForm')
      ->with([], $form_state)
      ->willReturn($plugin_selector_form);

    $payment_status_manager = $this->prophesize(PaymentStatusManagerInterface::class);
    $this->paymentStatusPluginType->getPluginManager()->willReturn($payment_status_manager->reveal());

    $build = $this->sut->form($form, $form_state);
    $this->assertIsArray($build);
    $this->assertArrayHasKey('payment_status', $build);
    $this->assertSame($plugin_selector_form, $build['payment_status']);

    // Build the form again to make sure the plugin selector is only created
    // once.
    $this->sut->form($form, $form_state);
  }

  /**
   * @covers ::validateForm
   * @covers ::getPluginSelector
   */
  public function testValidateForm() {
    $form = [
      'payment_status' => [
        'foo' => $this->randomMachineName(),
      ],
    ];
    $form_state = new FormState();

    $form_display = $this->prophesize(EntityFormDisplayInterface::class);
    $form_display->extractFormValues(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();
    $form_display->flagWidgetsErrorsFromViolations(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();
    $form_display->getComponents()->willReturn([]);
    $form_state->set('form_display', $form_display->reveal());

    $entity_type = new EntityType(['id' => 'payment']);
    $this->payment->expects($this->any())
      ->method('getEntityType')
      ->willReturn($entity_type);

    $this->payment->expects($this->any())
      ->method('validate')
      ->willReturn(new EntityConstraintViolationList($this->payment));

    $this->payment->expects($this->any())
      ->method('getFieldDefinitions')
      ->willReturn([]);

    $this->pluginSelectorManager->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->pluginSelector);

    $this->pluginSelector->expects($this->atLeastOnce())
      ->method('validateSelectorForm')
      ->with($form['payment_status'], $form_state);

    $payment_status_manager = $this->prophesize(PaymentStatusManagerInterface::class);
    $this->paymentStatusPluginType->getPluginManager()->willReturn($payment_status_manager->reveal());

    $this->sut->validateForm($form, $form_state);
  }

  /**
   * @covers ::submitForm
   * @covers ::getPluginSelector
   */
  public function testSubmitForm() {
    $form = [
      'payment_status' => [
        'foo' => $this->randomMachineName(),
      ],
    ];
    $form_state = new FormState();

    $form_display = $this->prophesize(EntityFormDisplayInterface::class);
    $form_display->extractFormValues(Argument::any(), $form, $form_state);
    $form_state->set('form_display', $form_display->reveal());

    $entity_type = new EntityType(['id' => 'payment']);
    $this->payment->expects($this->any())
      ->method('getEntityType')
      ->willReturn($entity_type);

    $payment_status = $this->createMock(PaymentStatusInterface::class);

    $this->pluginSelectorManager->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->pluginSelector);

    $this->pluginSelector->expects($this->atLeastOnce())
      ->method('getSelectedPlugin')
      ->willReturn($payment_status);
    $this->pluginSelector->expects($this->atLeastOnce())
      ->method('submitSelectorForm')
      ->with($form['payment_status'], $form_state);

    $url = new Url($this->randomMachineName());

    $this->payment->expects($this->once())
      ->method('setPaymentStatus')
      ->with($payment_status);
    $this->payment->expects($this->once())
      ->method('save');
    $this->payment->expects($this->once())
      ->method('toUrl')
      ->with('canonical')
      ->willReturn($url);

    $payment_status_manager = $this->prophesize(PaymentStatusManagerInterface::class);
    $this->paymentStatusPluginType->getPluginManager()->willReturn($payment_status_manager->reveal());

    $this->sut->submitForm($form, $form_state);
    $this->assertSame($url, $form_state->getRedirect());
  }

  /**
   * @covers ::actions
   */
  public function testActions() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $method = new \ReflectionMethod($this->sut, 'actions');
    $method->setAccessible(TRUE);
    $actions = $method->invokeArgs($this->sut, array($form, $form_state));
    $this->assertIsArray($actions);
  }

}

/**
 * Extends two interfaces, because we can only mock one.
 */
interface PaymentStatusFormUnitTestDummyPaymentMethodUpdateStatusInterface extends PaymentMethodUpdatePaymentStatusInterface, PaymentMethodInterface {
}
