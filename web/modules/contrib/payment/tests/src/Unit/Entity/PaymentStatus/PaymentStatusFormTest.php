<?php

namespace Drupal\Tests\payment\Unit\Entity\PaymentStatus {

  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Form\FormState;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Language\LanguageInterface;
  use Drupal\Core\Messenger\MessengerInterface;
  use Drupal\payment\Entity\PaymentStatus\PaymentStatusForm;
  use Drupal\payment\Entity\PaymentStatusInterface;
  use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
  use Drupal\plugin\PluginType\PluginTypeInterface;
  use Drupal\plugin\PluginType\PluginTypeManagerInterface;
  use Drupal\Tests\UnitTestCase;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * @coversDefaultClass \Drupal\payment\Entity\PaymentStatus\PaymentStatusForm
   *
   * @group Payment
   */
  class PaymentStatusFormTest extends UnitTestCase {

    /**
     * The payment status.
     *
     * @var \Drupal\payment\Entity\PaymentStatus|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentStatus;

    /**
     * The payment method configuration manager.
     *
     * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentStatusManager;

    /**
     * The payment status storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentStatusStorage;

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
     * The messenger.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messenger;

    /**
     * The form under test.
     *
     * @var \Drupal\payment\Entity\PaymentStatus\PaymentStatusForm
     */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

      $this->paymentStatusStorage = $this->createMock(EntityStorageInterface::class);

      $this->paymentStatus = $this->createMock(PaymentStatusInterface::class);

      $this->pluginSelectorManager = $this->createMock(PluginSelectorManagerInterface::class);

      $this->stringTranslation = $this->getStringTranslationStub();

      $this->paymentStatusPluginType = $this->prophesize(PluginTypeInterface::class);

      $this->messenger = $this->createMock(MessengerInterface::class);

      $this->sut = new PaymentStatusForm($this->stringTranslation, $this->paymentStatusStorage, $this->pluginSelectorManager, $this->paymentStatusPluginType->reveal());
      $this->sut->setEntity($this->paymentStatus);
      $this->sut->setMessenger($this->messenger);
    }

    /**
     * @covers ::create
     * @covers ::__construct
     */
    function testCreate() {
      $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
      $entity_type_manager->expects($this->any())
        ->method('getStorage')
        ->with('payment_status')
        ->willReturn($this->paymentStatusStorage);

      $plugin_type_manager = $this->prophesize(PluginTypeManagerInterface::class);
      $plugin_type_manager->getPluginType('payment_status')->willReturn($this->paymentStatusPluginType);

      $container = $this->createMock(ContainerInterface::class);
      $map = array(
        array('entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_type_manager),
        array('plugin.manager.plugin.plugin_selector', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->pluginSelectorManager),
        array('plugin.plugin_type_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $plugin_type_manager->reveal()),
        array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
      );
      $container->expects($this->any())
        ->method('get')
        ->willReturnMap($map);

      $form = PaymentStatusForm::create($container);
      $this->assertInstanceOf(PaymentStatusForm::class, $form);
    }

    /**
     * @covers ::form
     */
    public function testForm() {
      $label = $this->randomMachineName();
      $id = $this->randomMachineName();
      $is_new = FALSE;
      $parent_id = $this->randomMachineName();
      $description = $this->randomMachineName();

      $form_state = $this->createMock(FormStateInterface::class);

      $language = $this->createMock(LanguageInterface::class);

      $parent_selector_form = [
        '#foo' => $this->randomMachineName(),
      ];

      $parent_selector = $this->createMock(PluginSelectorInterface::class);
      $parent_selector->expects($this->atLeastOnce())
        ->method('buildSelectorForm')
        ->with([], $form_state)
        ->willReturn($parent_selector_form);

      $this->pluginSelectorManager->expects($this->atLeastOnce())
        ->method('createInstance')
        ->willReturn($parent_selector);

      $this->paymentStatus->expects($this->any())
        ->method('id')
        ->willReturn($id);
      $this->paymentStatus->expects($this->any())
        ->method('getDescription')
        ->willReturn($description);
      $this->paymentStatus->expects($this->any())
        ->method('getParentId')
        ->willReturn($parent_id);
      $this->paymentStatus->expects($this->any())
        ->method('isNew')
        ->willReturn($is_new);
      $this->paymentStatus->expects($this->any())
        ->method('label')
        ->willReturn($label);
      $this->paymentStatus->expects($this->any())
        ->method('language')
        ->willReturn($language);

      $build = $this->sut->form([], $form_state);
      unset($build['#process']);
      unset($build['langcode']);

      $expected_build['label'] = [
        '#type' => 'textfield',
        '#default_value' => $label,
        '#maxlength' => 255,
        '#required' => TRUE,
      ];
      unset($build['label']['#title']);
      $expected_build['id'] = [
        '#default_value' => $id,
        '#disabled' => !$is_new,
        '#machine_name' => array(
          'source' => array('label'),
          'exists' => array($this->sut, 'PaymentStatusIdExists'),
        ),
        '#maxlength' => 255,
        '#type' => 'machine_name',
        '#required' => TRUE,
      ];
      unset($build['id']['#title']);
      $expected_build['parent_id'] = $parent_selector_form;
      $expected_build['description'] = [
        '#type' => 'textarea',
        '#default_value' => $description,
        '#maxlength' => 255,
      ];
      unset($build['description']['#title']);
      $expected_build['#after_build'] = ['::afterBuild'];

      $this->assertSame($expected_build, $build);
    }

    /**
     * @covers ::copyFormValuesToEntity
     */
    public function testCopyFormValuesToEntity() {
      $description = $this->randomMachineName();
      $id = $this->randomMachineName();
      $label = $this->randomMachineName();
      $parent_id = $this->randomMachineName();

      $this->paymentStatus->expects($this->once())
        ->method('setDescription')
        ->with($description);
      $this->paymentStatus->expects($this->once())
        ->method('setId')
        ->with($id);
      $this->paymentStatus->expects($this->once())
        ->method('setLabel')
        ->with($label);
      $this->paymentStatus->expects($this->once())
        ->method('setParentId')
        ->with($parent_id);

      $parent_status = $this->createMock(PluginSelectorInterface::class);
      $parent_status->expects($this->atLeastOnce())
        ->method('getPluginId')
        ->willReturn($parent_id);

      $parent_selector = $this->createMock(PluginSelectorInterface::class);
      $parent_selector->expects($this->atLeastOnce())
        ->method('getSelectedPlugin')
        ->willReturn($parent_status);

      $this->pluginSelectorManager->expects($this->atLeastOnce())
        ->method('createInstance')
        ->willReturn($parent_selector);

      $form = [];
      $form_state = new FormState();
      $form_state->setValue('description', $description);
      $form_state->setValue('id', $id);
      $form_state->setValue('label', $label);
      $form_state->setValue('parent_id', $parent_id);

      $method = new \ReflectionMethod($this->sut, 'copyFormValuesToEntity');
      $method->setAccessible(TRUE);

      $method->invokeArgs($this->sut, array($this->paymentStatus, $form, $form_state));
    }

    /**
     * @covers ::paymentStatusIdExists
     */
    public function testPaymentStatusIdExists() {
      $method = new \ReflectionMethod($this->sut, 'paymentStatusIdExists');
      $method->setAccessible(TRUE);

      $payment_method_configuration_id = $this->randomMachineName();

      $this->paymentStatusStorage->expects($this->at(0))
        ->method('load')
        ->with($payment_method_configuration_id)
        ->willReturn($this->paymentStatus);
      $this->paymentStatusStorage->expects($this->at(1))
        ->method('load')
        ->with($payment_method_configuration_id)
        ->willReturn(NULL);

      $this->assertTrue($method->invoke($this->sut, $payment_method_configuration_id));
      $this->assertFalse($method->invoke($this->sut, $payment_method_configuration_id));
    }

    /**
     * @covers ::save
     */
    public function testSave() {
      $form_state = $this->createMock(FormStateInterface::class);
      $form_state->expects($this->once())
        ->method('setRedirect')
        ->with('entity.payment_status.collection');

      /** @var \Drupal\payment\Entity\PaymentStatus\PaymentStatusForm|\PHPUnit\Framework\MockObject\MockObject $form */
      $form = $this->getMockBuilder(PaymentStatusForm::class)
        ->setConstructorArgs(array($this->stringTranslation, $this->paymentStatusStorage, $this->pluginSelectorManager, $this->paymentStatusPluginType->reveal()))
        ->setMethods(array('copyFormValuesToEntity'))
        ->getMock();
      $form->setEntity($this->paymentStatus);
      $form->setMessenger($this->messenger);

      $this->paymentStatus->expects($this->once())
        ->method('save');

      $form->save([], $form_state);
    }

  }

}
