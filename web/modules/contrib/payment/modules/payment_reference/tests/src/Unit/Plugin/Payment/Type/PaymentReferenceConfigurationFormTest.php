<?php

namespace Drupal\Tests\payment_reference\Unit\Plugin\Payment\Type {

  use Drupal\Core\Form\FormState;
  use Drupal\Core\Messenger\MessengerInterface;
  use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
  use Drupal\payment_reference\Plugin\Payment\Type\PaymentReferenceConfigurationForm;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
  use Drupal\plugin\PluginType\PluginTypeInterface;
  use Drupal\plugin\PluginType\PluginTypeManagerInterface;
  use Drupal\Tests\UnitTestCase;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * @coversDefaultClass \Drupal\payment_reference\Plugin\Payment\Type\PaymentReferenceConfigurationForm
   *
   * @group Payment Reference Field
   */
  class PaymentReferenceConfigurationFormTest extends UnitTestCase {

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
     * The payment method manager.
     *
     * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodManager;

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
     * The plugin selector plugin type.
     *
     * @var \Drupal\plugin\PluginType\PluginTypeInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $pluginSelectorType;

    /**
     * The selected plugin selector.
     *
     * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $selectedPluginSelector;

    /**
     * The string translator.
     *
     * @var \Drupal\Core\StringTranslation\TranslationInterface
     */
    protected $stringTranslation;

    /**
     * The messenger.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messenger;

    /**
     * The class under test.
     *
     * @var \Drupal\payment_reference\Plugin\Payment\Type\PaymentReferenceConfigurationForm
     */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      $this->configFactoryConfiguration = array(
        'payment_reference.payment_type' => array(
          'limit_allowed_plugins' => TRUE,
          'allowed_plugin_ids' => array($this->randomMachineName()),
          'plugin_selector_id' => $this->randomMachineName(),
        ),
      );

      $this->configFactory = $this->getConfigFactoryStub($this->configFactoryConfiguration);

      $this->paymentMethodManager = $this->createMock(PaymentMethodManagerInterface::class);

      $this->pluginSelector = $this->createMock(PluginSelectorInterface::class);

      $this->pluginSelectorManager = $this->createMock(PluginSelectorManagerInterface::class);

      $this->stringTranslation = $this->getStringTranslationStub();

      $this->pluginSelectorType = $this->prophesize(PluginTypeInterface::class);
      $this->pluginSelectorType->getPluginManager()->willReturn($this->pluginSelectorManager);

      $this->selectedPluginSelector = $this->createMock(PluginSelectorInterface::class);

      $this->messenger = $this->createMock(MessengerInterface::class);

      $this->sut = new PaymentReferenceConfigurationForm($this->configFactory, $this->stringTranslation, $this->paymentMethodManager, $this->pluginSelectorType->reveal());
      $this->sut->setMessenger($this->messenger);
    }

    /**
     * @covers ::create
     * @covers ::__construct
     */
    function testCreate() {
      $plugin_type_manager = $this->prophesize(PluginTypeManagerInterface::class);
      $plugin_type_manager->getPluginType('plugin_selector')
        ->willReturn($this->pluginSelectorType->reveal());

      $container = $this->createMock(ContainerInterface::class);
      $map = array(
        array('config.factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->configFactory),
        array('plugin.manager.payment.method', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodManager),
        ['plugin.plugin_type_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $plugin_type_manager->reveal()],
        array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
      );
      $container->expects($this->any())
        ->method('get')
        ->willReturnMap($map);

      $sut = PaymentReferenceConfigurationForm::create($container);
      $this->assertInstanceOf(PaymentReferenceConfigurationForm::class, $sut);
    }

    /**
     * @covers ::getFormId
     */
    public function testGetFormId() {
      $this->assertIsString( $this->sut->getFormId());
    }

    /**
     * @covers ::buildForm
     * @covers ::getPluginSelector
     */
    public function testBuildForm() {
      $form = [];
      $form_state = new FormState();

      $map = [
        ['payment_radios', [], $this->pluginSelector],
        [$this->configFactoryConfiguration['payment_reference.payment_type']['plugin_selector_id'], [], $this->selectedPluginSelector],
      ];
      $this->pluginSelectorManager->expects($this->atLeast(count($map)))
        ->method('createInstance')
        ->willReturnMap($map);

      $this->pluginSelector->expects($this->once())
        ->method('buildSelectorForm')
        ->with([], $form_state)
        ->willReturn($this->pluginSelector);

      $this->paymentMethodManager->expects($this->atLeastOnce())
        ->method('getDefinitions')
        ->willReturn([]);

      $build = $this->sut->buildForm($form, $form_state);
      $this->assertIsArray($build);
    }

    /**
     * @covers ::validateForm
     * @covers ::getPluginSelector
     */
    public function testValidateForm() {
      $form = [
        'plugin_selector' => [
          'foo' => $this->randomMachineName(),
        ],
      ];
      $form_state = new FormState();
      $form_state->setValues([
        'plugin_selector_id' => $this->configFactoryConfiguration['payment_reference.payment_type']['plugin_selector_id'],
        'allowed_plugin_ids' => $this->configFactoryConfiguration['payment_reference.payment_type']['allowed_plugin_ids'],
        'limit_allowed_plugins' => $this->configFactoryConfiguration['payment_reference.payment_type']['limit_allowed_plugins'],
      ]);

      $map = [
        ['payment_radios', [], $this->pluginSelector],
        [$this->configFactoryConfiguration['payment_reference.payment_type']['plugin_selector_id'], [], $this->selectedPluginSelector],
      ];
      $this->pluginSelectorManager->expects($this->atLeast(count($map)))
        ->method('createInstance')
        ->willReturnMap($map);

      $this->pluginSelector->expects($this->once())
        ->method('validateSelectorForm')
        ->with($form['plugin_selector'], $form_state);

      $this->sut->validateForm($form, $form_state);
    }

    /**
     * @covers ::submitForm
     * @covers ::getPluginSelector
     */
    public function testSubmitForm() {
      $form = [
        'plugin_selector' => [
          'foo' => $this->randomMachineName(),
        ],
      ];
      $form_state = new FormState();
      $form_state->setValues([
        'plugin_selector_id' => $this->configFactoryConfiguration['payment_reference.payment_type']['plugin_selector_id'],
        'allowed_plugin_ids' => $this->configFactoryConfiguration['payment_reference.payment_type']['allowed_plugin_ids'],
        'limit_allowed_plugins' => $this->configFactoryConfiguration['payment_reference.payment_type']['limit_allowed_plugins'],
      ]);

      $map = [
        ['payment_radios', [], $this->pluginSelector],
        [$this->configFactoryConfiguration['payment_reference.payment_type']['plugin_selector_id'], [], $this->selectedPluginSelector],
      ];
      $this->pluginSelectorManager->expects($this->atLeast(count($map)))
        ->method('createInstance')
        ->willReturnMap($map);

      $this->pluginSelector->expects($this->once())
        ->method('submitSelectorForm')
        ->with($form['plugin_selector'], $form_state);
      $this->pluginSelector->expects($this->once())
        ->method('getSelectedPlugin')
        ->willReturn($this->selectedPluginSelector);

      $this->sut->submitForm($form, $form_state);
    }

  }

}
