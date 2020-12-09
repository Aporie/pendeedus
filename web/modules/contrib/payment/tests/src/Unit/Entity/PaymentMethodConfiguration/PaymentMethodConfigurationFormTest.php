<?php

namespace Drupal\Tests\payment\Unit\Entity\PaymentMethodConfiguration {

  use Drupal\Component\Plugin\ConfigurableInterface;
  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Form\FormState;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Form\FormValidatorInterface;
  use Drupal\Core\Language\LanguageInterface;
  use Drupal\Core\Messenger\MessengerInterface;
  use Drupal\Core\Session\AccountInterface;
  use Drupal\Core\Url;
  use Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationForm;
  use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
  use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationInterface as PaymentMethodConfigurationInterfacePlugin;
  use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
  use Drupal\Tests\UnitTestCase;
  use Drupal\user\UserInterface;
  use Symfony\Component\DependencyInjection\ContainerBuilder;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * @coversDefaultClass \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationForm
   *
   * @group Payment
   */
  class PaymentMethodConfigurationFormTest extends UnitTestCase {

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currentUser;

    /**
     * The form validator.
     *
     * @var \Drupal\Core\Form\FormValidatorInterface
     */
    protected $formValidator;

    /**
     * The payment method configuration.
     *
     * @var \Drupal\payment\Entity\PaymentMethodConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodConfiguration;

    /**
     * The payment method configuration manager.
     *
     * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodConfigurationManager;

    /**
     * The payment method configuration storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodConfigurationStorage;

    /**
     * The string translator.
     *
     * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
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
     * @var \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationForm
     */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      $this->currentUser = $this->createMock(AccountInterface::class);

      $this->formValidator = $this->createMock(FormValidatorInterface::class);

      $this->paymentMethodConfigurationManager = $this->createMock(PaymentMethodConfigurationManagerInterface::class);

      $this->paymentMethodConfigurationStorage = $this->createMock(EntityStorageInterface::class);

      $this->paymentMethodConfiguration = $this->createMock(PaymentMethodConfigurationInterface::class);

      $this->stringTranslation = $this->getStringTranslationStub();

      $this->messenger = $this->createMock(MessengerInterface::class);

      $container = new ContainerBuilder();
      $container->set('form_validator', $this->formValidator);
      \Drupal::setContainer($container);

      $this->sut = new PaymentMethodConfigurationForm($this->stringTranslation, $this->currentUser, $this->paymentMethodConfigurationStorage, $this->paymentMethodConfigurationManager);
      $this->sut->setEntity($this->paymentMethodConfiguration);
      $this->sut->setMessenger($this->messenger);
    }

    /**
     * @covers ::create
     * @covers ::__construct
     */
    function testCreate() {
      $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
      $map = array(
        array('payment_method_configuration', $this->paymentMethodConfigurationStorage),
      );
      $entity_type_manager->expects($this->atLeast(count($map)))
        ->method('getStorage')
        ->willReturnMap($map);

      $container = $this->createMock(ContainerInterface::class);
      $map = array(
        array('current_user', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->currentUser),
        array('entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_type_manager),
        array('plugin.manager.payment.method_configuration', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodConfigurationManager),
        array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
      );
      $container->expects($this->any())
        ->method('get')
        ->willReturnMap($map);

      $form = PaymentMethodConfigurationForm::create($container);
      $this->assertInstanceOf(PaymentMethodConfigurationForm::class, $form);
    }

    /**
     * @covers ::form
     *
     * @dataProvider providerTestForm
     */
    public function testForm($has_owner) {
      $payment_method_configuration_entity_id = $this->randomMachineName();
      $payment_method_configuration_entity_is_new = FALSE;
      $payment_method_configuration_entity_label = $this->randomMachineName();
      $payment_method_configuration_entity_status = TRUE;
      $payment_method_configuration_plugin_form = array(
        '#type' => $this->randomMachineName(),
      );
      $payment_method_configuration_plugin_id = $this->randomMachineName();
      $payment_method_configuration_plugin_configuration = array(
        'foo' => $this->randomMachineName(),
      );
      $payment_method_configuration_plugin_label = $this->randomMachineName();
      $payment_method_configuration_plugin_definition = array(
        'label' => $payment_method_configuration_plugin_label,
      );

      $owner = $this->createMock(UserInterface::class);

      $payment_method_configuration_plugin = $this->createMock(PaymentMethodConfigurationInterfacePlugin::class);

      $form = array(
        'plugin_form' => [],
      );
      $form_state = new FormState();

      $payment_method_configuration_plugin->expects($this->atLeastOnce())
        ->method('buildConfigurationForm')
        ->with([], $form_state)
        ->willReturn($payment_method_configuration_plugin_form);

      $this->paymentMethodConfigurationManager->expects($this->atLeastOnce())
        ->method('getDefinition')
        ->willReturn($payment_method_configuration_plugin_definition);

      $language = $this->createMock(LanguageInterface::class);

      $this->paymentMethodConfiguration->expects($this->atLeastOnce())
        ->method('getOwner')
        ->willReturn($has_owner ? $owner : NULL);
      $this->paymentMethodConfiguration->expects($this->atLeastOnce())
        ->method('getPluginConfiguration')
        ->willReturn($payment_method_configuration_plugin_configuration);
      $this->paymentMethodConfiguration->expects($this->any())
        ->method('getPluginId')
        ->willReturn($payment_method_configuration_plugin_id);
      $this->paymentMethodConfiguration->expects($this->any())
        ->method('id')
        ->willReturn($payment_method_configuration_entity_id);
      $this->paymentMethodConfiguration->expects($this->any())
        ->method('label')
        ->willReturn($payment_method_configuration_entity_label);
      $this->paymentMethodConfiguration->expects($this->any())
        ->method('language')
        ->willReturn($language);
      $this->paymentMethodConfiguration->expects($this->any())
        ->method('status')
        ->willReturn($payment_method_configuration_entity_status);

      $this->paymentMethodConfigurationManager->expects($this->once())
        ->method('createInstance')
        ->with($payment_method_configuration_plugin_id, $payment_method_configuration_plugin_configuration)
        ->willReturn($payment_method_configuration_plugin);

      $build = $this->sut->form($form, $form_state);
      // Make sure the payment method configuration plugin is instantiated only
      // once by building the form twice.
      $this->sut->form($form, $form_state);
      unset($build['#process']);
      unset($build['langcode']);
      $expected_build = array(
        'type' => array(
          '#type' => 'item',
          '#title' => 'Type',
          '#markup' => $payment_method_configuration_plugin_label,
        ),
        'status' => array(
          '#type' => 'checkbox',
          '#title' => 'Enabled',
          '#default_value' => $payment_method_configuration_entity_status,
        ),
        'label' => array(
          '#type' => 'textfield',
          '#title' => 'Label',
          '#default_value' => $payment_method_configuration_entity_label,
          '#maxlength' => 255,
          '#required' => TRUE,
        ),
        'id' => array(
          '#type' => 'machine_name',
          '#default_value' => $payment_method_configuration_entity_id,
          '#maxlength' => 255,
          '#required' => TRUE,
          '#machine_name' => array(
            'source' => array('label'),
            'exists' => array($this->sut, 'paymentMethodConfigurationIdExists'),
          ),
          '#disabled' => !$payment_method_configuration_entity_is_new,
        ),
        'owner' => array(
          '#target_type' => 'user',
          '#type' => 'entity_autocomplete',
          '#title' => 'Owner',
          '#default_value' => $has_owner ? $owner : $this->currentUser,
          '#required' => TRUE,
        ),
        'plugin_form' => array(
            '#tree' => TRUE,
          ) + $payment_method_configuration_plugin_form,
        '#after_build' => ['::afterBuild'],
      );
      $this->assertEquals($expected_build, $build);
    }

    /**
     * Provides data to self::testForm().
     */
    public function providerTestForm() {
      return [
        [TRUE],
        [FALSE],
      ];
    }

    /**
     * @covers ::copyFormValuesToEntity
     */
    public function testCopyFormValuesToEntity() {
      $label = $this->randomMachineName();
      $owner_id = mt_rand();
      $plugin_configuration = array(
        'bar' => $this->randomMachineName(),
      );
      $status = TRUE;

      $this->paymentMethodConfiguration->expects($this->once())
        ->method('setLabel')
        ->with($label);
      $this->paymentMethodConfiguration->expects($this->once())
        ->method('setOwnerId')
        ->with($owner_id);
      $this->paymentMethodConfiguration->expects($this->once())
        ->method('setPluginConfiguration')
        ->with($plugin_configuration);
      $this->paymentMethodConfiguration->expects($this->once())
        ->method('setStatus')
        ->with($status);

      $owner = $this->createMock(UserInterface::class);
      $owner->expects($this->any())
        ->method('id')
        ->willReturn($owner_id);

      $plugin = $this->createMock(PaymentMethodConfigurationFormTestPlugin::class);
      $plugin->expects($this->atLeastOnce())
        ->method('getConfiguration')
        ->willReturn($plugin_configuration);

      $form = [];
      $form_state = $this->createMock(FormStateInterface::class);
      $map = array(
        array('payment_method_configuration', $plugin),
        array('values', array(
          'label' => $label,
          'owner' => $owner_id,
          'status' => $status,
        )),
      );
      $form_state->expects($this->atLeastOnce())
        ->method('get')
        ->willReturnMap($map);
      $form_state->expects($this->atLeastOnce())
        ->method('getValues')
        ->willReturn(array(
          'label' => $label,
          'owner' => $owner_id,
          'status' => $status,
        ));

      $method = new \ReflectionMethod($this->sut, 'copyFormValuesToEntity');
      $method->setAccessible(TRUE);

      $method->invokeArgs($this->sut, array($this->paymentMethodConfiguration, $form, $form_state));
    }

    /**
     * @covers ::paymentMethodConfigurationIdExists
     */
    public function testPaymentMethodConfigurationIdExists() {
      $payment_method_configuration_id = $this->randomMachineName();

      $this->paymentMethodConfigurationStorage->expects($this->at(0))
        ->method('load')
        ->with($payment_method_configuration_id)
        ->willReturn($this->paymentMethodConfiguration);
      $this->paymentMethodConfigurationStorage->expects($this->at(1))
        ->method('load')
        ->with($payment_method_configuration_id)
        ->willReturn(NULL);

      $this->assertTrue($this->sut->paymentMethodConfigurationIdExists($payment_method_configuration_id));
      $this->assertFalse($this->sut->paymentMethodConfigurationIdExists($payment_method_configuration_id));
    }

    /**
     * @covers ::save
     */
    public function testSave() {
      $url = new Url($this->randomMachineName());

      $this->paymentMethodConfiguration->expects($this->once())
        ->method('save');
      $this->paymentMethodConfiguration->expects($this->atLeastOnce())
        ->method('toUrl')
        ->with('collection')
        ->willReturn($url);

      $form_state = $this->createMock(FormStateInterface::class);
      $form_state->expects($this->once())
        ->method('setRedirectUrl')
        ->with($url);

      /** @var \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationForm|\PHPUnit\Framework\MockObject\MockObject $form */
      $this->sut = $this->getMockBuilder(PaymentMethodConfigurationForm::class)
        ->setConstructorArgs(array($this->stringTranslation, $this->currentUser, $this->paymentMethodConfigurationStorage, $this->paymentMethodConfigurationManager))
        ->setMethods(array('copyFormValuesToEntity'))
        ->getMock();
      $this->sut->setEntity($this->paymentMethodConfiguration);
      $this->sut->setMessenger($this->messenger);

      $this->sut->save([], $form_state);
    }

    /**
     * @covers ::validateForm
     */
    public function testValidateForm() {
      /** @var \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationForm|\PHPUnit\Framework\MockObject\MockObject $form_object */
      $this->sut = $this->getMockBuilder(PaymentMethodConfigurationForm::class)
        ->setConstructorArgs(array($this->stringTranslation, $this->currentUser, $this->paymentMethodConfigurationStorage, $this->paymentMethodConfigurationManager))
        ->setMethods(array('copyFormValuesToEntity'))
        ->getMock();
      $this->sut->setEntity($this->paymentMethodConfiguration);

      $payment_method_configuration_plugin = $this->createMock(PaymentMethodConfigurationInterfacePlugin::class);

      $form = array(
        'plugin_form' => array(
          '#type' => $this->randomMachineName(),
        ),
      );
      $form_state = $this->createMock(FormStateInterface::class);
      $map = array(
        array('payment_method_configuration', $payment_method_configuration_plugin),
      );
      $form_state->expects($this->any())
        ->method('get')
        ->willReturnMap($map);

      $payment_method_configuration_plugin->expects($this->once())
        ->method('validateConfigurationForm')
        ->with($form['plugin_form'], $form_state);

      $this->sut->validateForm($form, $form_state);
    }

    /**
     * @covers ::submitForm
     */
    public function testSubmitForm() {
      /** @var \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationForm|\PHPUnit\Framework\MockObject\MockObject $form_object */
      $this->sut = $this->getMockBuilder(PaymentMethodConfigurationForm::class)
        ->setConstructorArgs(array($this->stringTranslation, $this->currentUser, $this->paymentMethodConfigurationStorage, $this->paymentMethodConfigurationManager))
        ->setMethods(array('copyFormValuesToEntity'))
        ->getMock();
      $this->sut->setEntity($this->paymentMethodConfiguration);

      $payment_method_configuration_plugin = $this->createMock(PaymentMethodConfigurationInterfacePlugin::class);

      $form = array(
        'plugin_form' => array(
          '#type' => $this->randomMachineName(),
        ),
      );
      $form_state = $this->createMock(FormStateInterface::class);
      $map = array(
        array('payment_method_configuration', $payment_method_configuration_plugin),
      );
      $form_state->expects($this->any())
        ->method('get')
        ->willReturnMap($map);

      $payment_method_configuration_plugin->expects($this->once())
        ->method('submitConfigurationForm')
        ->with($form['plugin_form'], $form_state);

      $this->sut->submitForm($form, $form_state);
    }

  }

  /**
   * Provides a payment method configuration plugin that provides configuration.
   */
  interface PaymentMethodConfigurationFormTestPlugin extends PaymentMethodConfigurationInterfacePlugin, ConfigurableInterface {

  }

}
