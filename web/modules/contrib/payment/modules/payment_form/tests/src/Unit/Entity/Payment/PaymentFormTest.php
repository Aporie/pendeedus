<?php

namespace Drupal\Tests\payment_form\Unit\Entity\Payment;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\OperationResultInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface;
use Drupal\payment\Response\ResponseInterface;
use Drupal\payment_form\Entity\Payment\PaymentForm;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\payment_form\Entity\Payment\PaymentForm
 *
 * @group Payment Form Field
 */
class PaymentFormTest extends UnitTestCase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The configuration the config factory returns.
   *
   * @see self::__construct
   *
   * @var array
   */
  protected $configFactoryConfiguration = [];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

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
   * A payment entity.
   *
   * @var \Drupal\payment\Entity\Payment|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The payment method manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentMethodManager;

  /**
   * The payment method plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $paymentMethodType;

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
   * The string translation.
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
   * The class under test.
   *
   * @var \Drupal\payment_form\Entity\Payment\PaymentForm
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $this->formDisplay = $this->createMock(EntityFormDisplayInterface::class);

    $this->payment = $this->createMock(PaymentInterface::class);

    $this->paymentMethodManager = $this->createMock(PaymentMethodManagerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->paymentMethodType = $this->prophesize(PluginTypeInterface::class);
    $this->paymentMethodType->getPluginManager()->willReturn($this->paymentMethodManager);

    $this->pluginSelector = $this->createMock(PluginSelectorInterface::class);

    $this->pluginSelectorManager = $this->createMock(PluginSelectorManagerInterface::class);

    $this->configFactoryConfiguration = [
      'payment_form.payment_type' => [
        'limit_allowed_plugins' => TRUE,
        'allowed_plugin_ids' => [$this->randomMachineName()],
        'plugin_selector_id' => $this->randomMachineName(),
      ],
    ];

    $this->configFactory = $this->getConfigFactoryStub($this->configFactoryConfiguration);

    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class)->reveal();
    $this->time = $this->prophesize(TimeInterface::class)->reveal();

    $this->sut = new PaymentForm($this->entityRepository, $this->entityTypeBundleInfo, $this->time);
    $this->sut->setStringTranslation($this->stringTranslation);
    $this->sut->setConfigFactory($this->configFactory);
    $this->sut->setEntity($this->payment);
    $this->sut->setPluginSelectorManager($this->pluginSelectorManager);
    $this->sut->setPaymentMethodPluginType($this->paymentMethodType->reveal());
    $this->sut->setCurrentUser($this->currentUser);
  }

  /**
   * @covers ::form
   * @covers ::getPluginSelector
   * @covers ::getPaymentMethodManager
   */
  public function testForm() {
    $plugin_selector_build = [
      '#type' => $this->randomMachineName(),
    ];
    $this->pluginSelector->expects($this->atLeastOnce())
      ->method('buildSelectorForm')
      ->willReturn($plugin_selector_build);

    $this->pluginSelectorManager->expects($this->once())
      ->method('createInstance')
      ->with($this->configFactoryConfiguration['payment_form.payment_type']['plugin_selector_id'])
      ->willReturn($this->pluginSelector);

    $payment_type = $this->createMock(PaymentTypeInterface::class);
    $this->payment->expects($this->any())
      ->method('getPaymentType')
      ->willReturn($payment_type);
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $this->payment->expects($this->any())
      ->method('getEntityType')
      ->willReturn($entity_type);

    $form = [
      'langcode' => [],
    ];
    $form_state = new FormState();
    $this->sut->setFormDisplay($this->formDisplay, $form_state);
    $build = $this->sut->form($form, $form_state);
    // Build the form a second time to make sure the plugin selector is only
    // instantiated once.
    $this->sut->form($form, $form_state);
    $this->assertIsArray($build);
    $this->assertArrayHasKey('line_items', $build);
    $this->assertSame($this->payment, $build['line_items']['#payment_line_items']);
    $this->assertArrayHasKey('payment_method', $build);
    $this->assertSame($plugin_selector_build, $build['payment_method']);
  }

  /**
   * @covers ::validateForm
   * @covers ::getPluginSelector
   * @covers ::getPaymentMethodManager
   */
  public function testValidateForm() {
    $form = [
      'payment_method' => [
        '#type' => $this->randomMachineName(),
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
      ->with($this->configFactoryConfiguration['payment_form.payment_type']['plugin_selector_id'])
      ->willReturn($this->pluginSelector);

    $this->pluginSelector->expects($this->once())
      ->method('validateSelectorForm')
      ->with($form['payment_method'], $form_state);

    $this->sut->validateForm($form, $form_state);
  }

  /**
   * @covers ::submitForm
   * @covers ::getPluginSelector
   * @covers ::getPaymentMethodManager
   */
  public function testSubmitForm() {
    $symfony_response = $this->prophesize(Response::class);
    $completion_response = $this->prophesize(ResponseInterface::class);
    $completion_response->getResponse()->willReturn($symfony_response->reveal());

    $result = $this->createMock(OperationResultInterface::class);
    $result->expects($this->atLeastOnce())
      ->method('getCompletionResponse')
      ->willReturn($completion_response->reveal());

    $form = [
      'payment_method' => [
        '#type' => $this->randomMachineName(),
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

    $payment_method = $this->createMock(PaymentMethodInterface::class);

    $this->pluginSelectorManager->expects($this->once())
      ->method('createInstance')
      ->with($this->configFactoryConfiguration['payment_form.payment_type']['plugin_selector_id'])
      ->willReturn($this->pluginSelector);

    $this->pluginSelector->expects($this->atLeastOnce())
      ->method('getSelectedPlugin')
      ->willReturn($payment_method);
    $this->pluginSelector->expects($this->atLeastOnce())
      ->method('submitSelectorForm')
      ->with($form['payment_method'], $form_state);

    $this->payment->expects($this->atLeastOnce())
      ->method('setPaymentMethod')
      ->with($payment_method);
    $this->payment->expects($this->atLeastOnce())
      ->method('save');
    $this->payment->expects($this->atLeastOnce())
      ->method('execute')
      ->willReturn($result);

    $this->sut->submitForm($form, $form_state);
    $this->assertSame($symfony_response->reveal(), $form_state->getResponse());
  }

  /**
   * @covers ::actions
   * @covers ::getPaymentMethodManager
   */
  public function testActionsWithAvailablePlugins() {
    $form = [];
    $form_state = new FormState();
    $form_state->set('plugin_selector', $this->pluginSelector);

    $plugin_id_a = reset($this->configFactoryConfiguration['payment_form.payment_type']['allowed_plugin_ids']);
    $plugin_id_b = $this->randomMachineName();

    $plugin_a = $this->createMock(PaymentMethodInterface::class);
    $plugin_a->expects($this->atLeastOnce())
      ->method('executePaymentAccess')
      ->with($this->currentUser)
      ->willReturn(AccessResult::allowed());

    $plugin_definitions = [
      $plugin_id_a => [
        'id' => $plugin_id_a,
      ],
      $plugin_id_b => [
        'id' => $plugin_id_b,
      ],
    ];

    $this->paymentMethodManager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);
    $this->paymentMethodManager->expects($this->once())
      ->method('createInstance')
      ->with($plugin_id_a)
      ->willReturn($plugin_a);

    $this->configFactory = $this->getConfigFactoryStub($this->configFactoryConfiguration);

    $method = new \ReflectionMethod($this->sut, 'actions');
    $method->setAccessible(TRUE);
    $actions = $method->invokeArgs($this->sut, [$form, $form_state]);
    $this->assertFalse($actions['submit']['#disabled']);
  }

  /**
   * @covers ::actions
   * @covers ::getPaymentMethodManager
   */
  public function testActionsWithoutAvailablePlugins() {
    $form = [];
    $form_state = new FormState();
    $form_state->set('plugin_selector', $this->pluginSelector);

    $this->paymentMethodManager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn([]);

    $method = new \ReflectionMethod($this->sut, 'actions');
    $method->setAccessible(TRUE);
    $actions = $method->invokeArgs($this->sut, [$form, $form_state]);
    $this->assertTrue($actions['submit']['#disabled']);
  }

}
