<?php

namespace Drupal\Tests\payment\Unit\Element {

  use Drupal\Component\Plugin\PluginManagerInterface;
  use Drupal\Component\Utility\Random;
  use Drupal\Core\Ajax\AjaxResponse;
  use Drupal\Core\Config\TypedConfigManagerInterface;
  use Drupal\Core\Datetime\DateFormatter;
  use Drupal\Core\DependencyInjection\ClassResolverInterface;
  use Drupal\Core\DependencyInjection\Container;
  use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Form\FormState;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Messenger\MessengerInterface;
  use Drupal\Core\Render\RendererInterface;
  use Drupal\Core\Routing\UrlGeneratorInterface;
  use Drupal\Core\Session\AccountInterface;
  use Drupal\Core\Url;
  use Drupal\Core\Utility\LinkGeneratorInterface;
  use Drupal\currency\Entity\CurrencyInterface;
  use Drupal\payment\Element\PaymentReferenceBase;
  use Drupal\payment\Entity\PaymentInterface;
  use Drupal\payment\OperationResultInterface;
  use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
  use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
  use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
  use Drupal\payment\QueueInterface;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
  use Drupal\plugin\PluginType\PluginType;
  use Drupal\Tests\UnitTestCase;
  use Symfony\Component\DependencyInjection\ContainerInterface;
  use Symfony\Component\HttpFoundation\Request;
  use Symfony\Component\HttpFoundation\RequestStack;

  /**
   * @coversDefaultClass \Drupal\payment\Element\PaymentReferenceBase
   *
   * @group Payment
   */
  class PaymentReferenceBaseTest extends UnitTestCase {

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $container;

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currentUser;

    /**
     * The date formatter.
     *
     * @var \Drupal\Core\Datetime\DateFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateFormatter;

    /**
     * The link generator.
     *
     * @var \Drupal\Core\Utility\LinkGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $linkGenerator;

    /**
     * The payment method manager.
     *
     * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodManager;

    /**
     * The payment method type.
     *
     * @var \Drupal\plugin\PluginType\PluginTypeInterface
     */
    protected $paymentMethodType;

    /**
     * The payment queue.
     *
     * @var \Drupal\payment\QueueInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentQueue;

    /**
     * The payment storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentStorage;

    /**
     * The plugin definition.
     *
     * @var array
     */
    protected $pluginDefinition = [];

    /**
     * The plugin selector manager.
     *
     * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $pluginSelectorManager;

    /**
     * The renderer.
     *
     * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $renderer;

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
     * The messenger.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messenger;

    /**
     * The class under test.
     *
     * @var \Drupal\payment\Element\PaymentReferenceBase|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $sut;

    /**
     * The url generator.
     *
     * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $urlGenerator;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      $plugin_type_id = $this->randomMachineName();
      $plugin_manager_service_id = 'foo.bar';
      $plugin_configuration_schema_id = sprintf('plugin.plugin_configuration.%s.*', $plugin_type_id);
      $plugin_type_definition = [
        'id' => $plugin_type_id,
        'label' => $this->randomMachineName(),
        'provider' => $this->randomMachineName(),
        'plugin_manager_service_id' => $plugin_manager_service_id,
      ];

      $this->currentUser = $this->createMock(AccountInterface::class);

      $this->dateFormatter = $this->getMockBuilder(DateFormatter::class)
        ->disableOriginalConstructor()
        ->getMock();

      $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);

      $this->paymentMethodManager = $this->createMock(PaymentMethodManagerInterface::class);

      $this->container = $this->prophesize(ContainerInterface::class);
      $this->container->get($plugin_manager_service_id)->willReturn($this->paymentMethodManager);

      $class_resolver = $this->createMock(ClassResolverInterface::class);

      $this->stringTranslation = $this->getStringTranslationStub();

      $this->messenger = $this->createMock(MessengerInterface::class);

      $typed_config_manager = $this->prophesize(TypedConfigManagerInterface::class);
      $typed_config_manager->hasConfigSchema($plugin_configuration_schema_id)->willReturn(TRUE);

      $this->paymentMethodType = new PluginType($plugin_type_definition, $this->container->reveal(), $this->stringTranslation, $class_resolver, $typed_config_manager->reveal());

      $this->paymentQueue = $this->createMock(QueueInterface::class);

      $this->paymentStorage = $this->createMock(EntityStorageInterface::class);

      $this->pluginSelectorManager = $this->createMock(PluginSelectorManagerInterface::class);

      $this->renderer = $this->createMock(RendererInterface::class);

      $this->requestStack = $this->getMockBuilder(RequestStack::class)
        ->disableOriginalConstructor()
        ->getMock();

      $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

      $container = new Container();
      $container->set('renderer', $this->renderer);
      \Drupal::setContainer($container);

      $configuration = [];
      $plugin_id = $this->randomMachineName();

      $this->pluginDefinition['class'] = $this->randomMachineName();

      $this->sut = $this->getMockBuilder(PaymentReferenceBase::class)
        ->setConstructorArgs(array($configuration, $plugin_id, $this->pluginDefinition, $this->requestStack, $this->paymentStorage, $this->stringTranslation, $this->dateFormatter, $this->linkGenerator, $this->renderer, $this->currentUser, $this->pluginSelectorManager, $this->paymentMethodType, new Random()))
        ->getMockForAbstractClass();
      $this->sut->expects($this->any())
        ->method('getPaymentQueue')
        ->willReturn($this->paymentQueue);
      $this->sut->setMessenger($this->messenger);
    }

    /**
     * @covers ::getInfo
     */
    public function testGetInfo() {
      $info = $this->sut->getInfo();
      $this->assertIsArray($info);
      $this->assertTrue(is_callable($info['#process'][0]));
    }

    /**
     * @covers ::valueCallback
     */
    public function testValueCallbackWithDefaultValue() {
      $payment_id = mt_rand();
      $input = $this->randomMachineName();
      $form_state = $this->createMock(FormStateInterface::class);
      $element = array(
        '#default_value' => $payment_id,
      );

      $element_sut = $this->sut;
      $this->assertSame($payment_id, $element_sut::valueCallback($element, $input, $form_state));
    }

    /**
     * @covers ::valueCallback
     */
    public function testValueCallbackWithoutDefaultValue() {
      $queue_category_id = $this->randomMachineName();
      $queue_owner_id = $this->randomMachineName();
      $payment_id = mt_rand();

      $element = array(
        '#default_value' => NULL,
        '#queue_category_id' => $queue_category_id,
        '#queue_owner_id' => $queue_owner_id,
        '#type' => $this->randomMachineName(),
      );
      $input = $this->randomMachineName();
      $form_state = $this->createMock(FormStateInterface::class);

      $this->sut = $this->getMockBuilder(PaymentReferenceBase::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
      $this->sut->expects($this->atLeastOnce())
        ->method('getPaymentQueue')
        ->willReturn($this->paymentQueue);

      // We cannot mock ElementInfoManagerInterface, because it does not extend
      // PluginManagerInterface.
      $element_info_manager = $this->createMock(PluginManagerInterface::class);
      $element_info_manager->expects($this->once())
        ->method('createInstance')
        ->with($element['#type'])
        ->willReturn($this->sut);

      $container = $this->createMock(ContainerInterface::class);
      $container->expects($this->once())
        ->method('get')
        ->with('plugin.manager.element_info')
        ->willReturn($element_info_manager);

      \Drupal::setContainer($container);

      $this->paymentQueue->expects($this->once())
        ->method('loadPaymentIds')
        ->with($queue_category_id, $queue_owner_id)
        ->willReturn(array($payment_id));

      $element_sut = $this->sut;
      $this->assertSame($payment_id, $element_sut::valueCallback($element, $input, $form_state));
    }

    /**
     * @covers ::pay
     *
     * @dataProvider providerTestPay
     */
    public function testPay($has_completed, $is_xml_http_request) {
      $configuration = [];
      $plugin_id = $this->randomMachineName();
      $this->pluginDefinition = [];

      $this->sut = $this->getMockBuilder(PaymentReferenceBase::class)
        ->setConstructorArgs(array($configuration, $plugin_id, $this->pluginDefinition, $this->requestStack, $this->paymentStorage, $this->stringTranslation, $this->dateFormatter, $this->linkGenerator, $this->renderer, $this->currentUser, $this->pluginSelectorManager, $this->paymentMethodType, new Random()))
        ->setMethods(array('getEntityFormDisplay', 'getPluginSelector'))
        ->getMockForAbstractClass();
      $this->sut->setMessenger($this->messenger);

      $payment_method = $this->createMock(PaymentMethodInterface::class);

      $url = new Url($this->randomMachineName());

      $result = $this->createMock(OperationResultInterface::class);
      $result->expects($this->atLeastOnce())
        ->method('isCompleted')
        ->willReturn($has_completed);

      $payment = $this->createMock(PaymentInterface::class);
      $payment->expects($this->atLeastOnce())
        ->method('createDuplicate')
        ->willReturnSelf();
      $payment->expects($this->atLeastOnce())
        ->method('execute')
        ->willReturn($result);
      $payment->expects($this->atLeastOnce())
        ->method('setPaymentMethod')
        ->with($payment_method);
      $payment->expects(!$has_completed && !$is_xml_http_request ? $this->atLeastOnce() : $this->never())
        ->method('toUrl')
        ->with('complete')
        ->willReturn($url);

      $form = array(
        'foo' => array(
          'bar' => array(
            '#prototype_payment' => $payment,
            'container' => array(
              '#id' => $this->randomMachineName(),
              'payment_form' => array(
                'pay' => array(
                  '#array_parents' => array('foo', 'bar', 'container', 'payment_form', 'pay'),
                ),
                'payment_method' => array(
                  '#foo' => $this->randomMachineName(),
                ),
              ),
            ),
          ),
        ),
      );
      $form_state = new FormState();
      $form_state->setTriggeringElement($form['foo']['bar']['container']['payment_form']['pay']);

      $request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();
      $request->expects($has_completed ? $this->never() : $this->atLeastOnce())
        ->method('isXmlHttpRequest')
        ->willReturn($is_xml_http_request);

      $this->requestStack->expects($has_completed ? $this->never() : $this->atLeastOnce())
        ->method('getCurrentRequest')
        ->willReturn($request);

      $plugin_selector = $this->createMock(PluginSelectorInterface::class);
      $plugin_selector->expects($this->atLeastOnce())
        ->method('getSelectedPlugin')
        ->willReturn($payment_method);
      $plugin_selector->expects($this->once())
        ->method('submitSelectorForm')
        ->with($form['foo']['bar']['container']['payment_form']['payment_method'], $form_state);

      $entity_form_display = $this->createMock(EntityFormDisplayInterface::class);
      $entity_form_display->expects($this->once())
        ->method('extractFormValues')
        ->with($payment, $form['foo']['bar']['container']['payment_form'], $form_state);

      $this->sut->expects($this->atLeastOnce())
        ->method('getEntityFormDisplay')
        ->willReturn($entity_form_display);
      $this->sut->expects($this->atLeastOnce())
        ->method('getPluginSelector')
        ->willReturn($plugin_selector);

      $this->sut->pay($form, $form_state);

      $this->assertTrue($form_state->isRebuilding());
    }

    /**
     * Provides data to self::testPay().
     */
    public function providerTestPay() {
      return array(
        array(TRUE, TRUE),
        array(TRUE, FALSE),
        array(FALSE, FALSE),
      );
    }

    /**
     * @covers ::ajaxPay
     *
     * @dataProvider providerTestAjaxPay
     */
    public function testAjaxPay($is_completed, $number_of_commands) {
      $payment = $this->createMock(PaymentInterface::class);

      $result = $this->createMock(OperationResultInterface::class);
      $result->expects($this->atLeastOnce())
        ->method('isCompleted')
        ->willReturn($is_completed);

      $payment_method = $this->createMock(PaymentMethodInterface::class);
      $payment_method->expects($this->any())
        ->method('getPayment')
        ->willReturn($payment);
      $payment_method->expects($this->atLeastOnce())
        ->method('getPaymentExecutionResult')
        ->willReturn($result);
      $payment_method->expects($this->any())
        ->method('getPluginDefinition')
        ->willReturn(['label' => 'Example']);

      $plugin_selector_plugin_id = $this->randomMachineName();

      $plugin_selector = $this->createMock(PluginSelectorInterface::class);
      $plugin_selector->expects($this->atLeastOnce())
        ->method('getSelectedPlugin')
        ->willReturn($payment_method);

      $form = array(
        'foo' => array(
          'bar' => array(
            '#limit_allowed_plugin_ids' => [],
            '#name' => $this->randomMachineName(),
            '#plugin_selector_id' => $plugin_selector_plugin_id,
            '#prototype_payment' => $payment,
            '#required' => TRUE,
            'container' => array(
              '#id' => $this->randomMachineName(),
              'payment_form' => array(
                'pay' => array(
                  '#array_parents' => array('foo', 'bar', 'container', 'payment_form', 'pay'),
                ),
              ),
            ),
          ),
        ),
      );
      $form_state = new FormState();
      $form_state->set('payment_reference.element.payment_reference.plugin_selector.' . $form['foo']['bar']['#name'], $plugin_selector);
      $form_state->setTriggeringElement($form['foo']['bar']['container']['payment_form']['pay']);

      $response = $this->sut->ajaxPay($form, $form_state);
      $this->assertInstanceOf(AjaxResponse::class, $response);
      $this->assertCount($number_of_commands, $response->getCommands());
    }

    /**
     * Provides data to self::testAjaxPay().
     */
    public function providerTestAjaxPay() {
      return array(
      array(TRUE, 1),
      array(FALSE, 2),
      );
    }

    /**
     * @covers ::refresh
     */
    public function testRefresh() {
      $form = [];
      $form_state = $this->createMock(FormStateInterface::class);
      $form_state->expects($this->once())
        ->method('setRebuild')
        ->with(TRUE);

      $this->sut->refresh($form, $form_state);
    }

    /**
     * @covers ::ajaxRefresh
     */
    public function testAjaxRefresh() {
      $form = array(
        'foo' => array(
          'bar' => array(
            'container' => array(
              '#id' => $this->randomMachineName(),
              'refresh' => array(
                '#array_parents' => array('foo', 'bar', 'container', 'refresh'),
              ),
            ),
          ),
        ),
      );
      $form_state = $this->createMock(FormStateInterface::class);
      $form_state->expects($this->once())
        ->method('getTriggeringElement')
        ->willReturn($form['foo']['bar']['container']['refresh']);

      $response = $this->sut->ajaxRefresh($form, $form_state);
      $this->assertInstanceOf(AjaxResponse::class, $response);
    }

    /**
     * @covers ::disableChildren
     */
    public function testDisableChildren() {
      $element = array(
        'foo' => array(
          '#foo' => $this->randomMachineName(),
          'bar' => array(
            '#bar' => $this->randomMachineName(),
          ),
        ),
      );

      $expected_element = $element;
      $expected_element['foo']['#disabled'] = TRUE;
      $expected_element['foo']['bar']['#disabled'] = TRUE;

      $method = new \ReflectionMethod($this->sut, 'disableChildren');
      $method->setAccessible(TRUE);

      $method->invokeArgs($this->sut, array(&$element));
      $this->assertSame($expected_element, $element);
    }

    /**
     * @covers ::getPluginSelector
     *
     * @dataProvider providerGetPluginSelector
     */
    public function testGetPluginSelector($limit_allowed_plugin_ids) {
      $payment = $this->createMock(PaymentInterface::class);
      $payment->expects($this->once())
        ->method('createDuplicate')
        ->willReturnSelf();

      $plugin_selector_plugin_id = $this->randomMachineName();
      $required = $this->randomMachineName();

      $element = array(
        '#limit_allowed_plugin_ids' => $limit_allowed_plugin_ids,
        '#name' => $this->randomMachineName(),
        '#plugin_selector_id' => $plugin_selector_plugin_id,
        '#prototype_payment' => $payment,
        '#required' => $required,
      );
      $form_state = new FormState();

      $plugin_selector = $this->createMock(PluginSelectorInterface::class);
      $plugin_selector->expects($this->once())
        ->method('setRequired')
        ->with($required);

      $this->pluginSelectorManager->expects($this->once())
        ->method('createInstance')
        ->with($plugin_selector_plugin_id)
        ->willReturn($plugin_selector);

      $method = new \ReflectionMethod($this->sut, 'getPluginSelector');
      $method->setAccessible(TRUE);

      $retrieved_plugin_selector = $method->invoke($this->sut, $element, $form_state);
      $this->assertInstanceOf(PluginSelectorInterface::class, $retrieved_plugin_selector);
      $this->assertSame($retrieved_plugin_selector, $method->invoke($this->sut, $element, $form_state));
    }

    /**
     * Provides data to self::testGetPluginSelector().
     */
    public function providerGetPluginSelector() {
      return array(
        array(NULL),
        array([]),
        array(array($this->randomMachineName(), $this->randomMachineName())),
      );
    }

    /**
     * @covers ::buildCompletePaymentLink
     */
    public function testBuildCompletePaymentLinkWithoutPaymentMethod() {
      $configuration = [];
      $plugin_id = $this->randomMachineName();
      $this->pluginDefinition = [];

      $element = array(
        '#foo' => $this->randomMachineName(),
      );
      $form_state = $this->createMock(FormStateInterface::class);

      $plugin_selector = $this->createMock(PluginSelectorInterface::class);

      $this->sut = $this->getMockBuilder(PaymentReferenceBase::class)
        ->setConstructorArgs(array($configuration, $plugin_id, $this->pluginDefinition, $this->requestStack, $this->paymentStorage, $this->stringTranslation, $this->dateFormatter, $this->linkGenerator, $this->renderer, $this->currentUser, $this->pluginSelectorManager, $this->paymentMethodType, new Random()))
        ->setMethods(array('getPluginSelector'))
        ->getMockForAbstractClass();
      $this->sut->expects($this->atLeastOnce())
        ->method('getPluginSelector')
        ->with($element, $form_state)
        ->willReturn($plugin_selector);

      $method = new \ReflectionMethod($this->sut, 'buildCompletePaymentLink');
      $method->setAccessible(TRUE);

      $build = $method->invoke($this->sut, $element, $form_state);
      $this->assertSame([], $build);
    }

    /**
     * @covers ::buildCompletePaymentLink
     */
    public function testBuildCompletePaymentLinkWithPaymentMethod() {
      $configuration = [];
      $plugin_id = $this->randomMachineName();
      $this->pluginDefinition = [];

      $element = array(
        '#foo' => $this->randomMachineName(),
      );
      $form_state = $this->createMock(FormStateInterface::class);

      $payment = $this->createMock(PaymentInterface::class);

      $payment_method = $this->createMock(PaymentMethodInterface::class);
      $payment_method->expects($this->atLeastOnce())
        ->method('getPayment')
        ->willReturn($payment);
      $payment_method->expects($this->atLeastOnce())
        ->method('getPluginDefinition')
        ->willReturn(['label' => 'Example']);

      $plugin_selector = $this->createMock(PluginSelectorInterface::class);
      $plugin_selector->expects($this->atLeastOnce())
        ->method('getSelectedPlugin')
        ->willReturn($payment_method);

      $this->sut = $this->getMockBuilder(PaymentReferenceBase::class)
        ->setConstructorArgs(array($configuration, $plugin_id, $this->pluginDefinition, $this->requestStack, $this->paymentStorage, $this->stringTranslation, $this->dateFormatter, $this->linkGenerator, $this->renderer, $this->currentUser, $this->pluginSelectorManager, $this->paymentMethodType, new Random()))
        ->setMethods(array('getPluginSelector'))
        ->getMockForAbstractClass();
      $this->sut->expects($this->atLeastOnce())
        ->method('getPluginSelector')
        ->with($element, $form_state)
        ->willReturn($plugin_selector);


      $method = new \ReflectionMethod($this->sut, 'buildCompletePaymentLink');
      $method->setAccessible(TRUE);

      $build = $method->invoke($this->sut, $element, $form_state);
      $this->assertIsArray($build);
      $this->assertSame('link', $build['link']['#type']);
    }

    /**
     * @covers ::buildPaymentView
     */
    public function testBuildPaymentViewWithoutPaymentWithDefaultValue() {
      $element = array(
        '#default_value' => mt_rand(),
        '#available_payment_id' => mt_rand(),
      );
      $form_state = $this->createMock(FormStateInterface::class);

      $this->paymentStorage->expects($this->once())
        ->method('load')
        ->with($element['#default_value']);

      $method = new \ReflectionMethod($this->sut, 'buildPaymentView');
      $method->setAccessible(TRUE);

      $build = $method->invoke($this->sut, $element, $form_state);
      $this->assertSame([], $build);
    }

    /**
     * @covers ::buildPaymentView
     */
    public function testBuildPaymentViewWithoutPaymentWithOutDefaultValue() {
      $element = array(
        '#default_value' => NULL,
        '#available_payment_id' => mt_rand(),
      );
      $form_state = $this->createMock(FormStateInterface::class);

      $this->paymentStorage->expects($this->once())
        ->method('load')
        ->with($element['#available_payment_id']);

      $method = new \ReflectionMethod($this->sut, 'buildPaymentView');
      $method->setAccessible(TRUE);

      $build = $method->invoke($this->sut, $element, $form_state);
      $this->assertSame([], $build);
    }

    /**
     * @covers ::buildPaymentView
     *
     * @dataProvider providerTestBuildPaymentViewWithPayment
     */
    public function testBuildPaymentViewWithPayment($view_access) {
      $element = array(
        '#default_value' => mt_rand(),
      );
      $form_state = $this->createMock(FormStateInterface::class);

      $currency = $this->createMock(CurrencyInterface::class);

      $payment_status = $this->createMock(PaymentStatusInterface::class);
      $payment_status->expects($this->atLeastOnce())
        ->method('getPluginDefinition')
        ->willReturn(['label' => 'Example']);

      $payment = $this->createMock(PaymentInterface::class);
      $payment->expects($this->atLeastOnce())
        ->method('access')
        ->willReturn($view_access);
      $payment->expects($this->atLeastOnce())
        ->method('getCurrency')
        ->willReturn($currency);
      $payment->expects($this->atLeastOnce())
        ->method('getPaymentStatus')
        ->willReturn($payment_status);

      $urlObject = $this->getMockBuilder(Url::class)->disableOriginalConstructor()->getMock();
      $urlObject->expects($view_access ? $this->once() : $this->never())
        ->method('toString');

      $payment->expects($view_access ? $this->once() : $this->never())
        ->method('toUrl')
        ->willReturn($urlObject);

      $this->paymentStorage->expects($this->once())
        ->method('load')
        ->with($element['#default_value'])
        ->willReturn($payment);

      $method = new \ReflectionMethod($this->sut, 'buildPaymentView');
      $method->setAccessible(TRUE);

      $build = $method->invoke($this->sut, $element, $form_state);
      $this->assertIsArray($build);
    }

    /**
     * Provides data to self::testBuildPaymentViewWithPayment().
     */
    public function providerTestBuildPaymentViewWithPayment() {
      return array(
        array(TRUE),
        array(FALSE),
      );
    }

    /**
     * @covers ::buildRefreshButton
     */
    public function testBuildRefreshButton() {
      $limit_allowed_plugin_ids = [$this->randomMachineName()];

      $plugin_selector_id = $this->randomMachineName();

      $plugin_selector = $this->createMock(PluginSelectorInterface::class);

      $this->pluginSelectorManager->expects($this->atLeastOnce())
        ->method('createInstance')
        ->with($plugin_selector_id)
        ->willReturn($plugin_selector);

      $payment = $this->createMock(PaymentInterface::class);
      $payment->expects($this->atLeastOnce())
        ->method('createDuplicate')
        ->willReturnSelf();

      $element = array(
        '#default_value' => mt_rand(),
        'container' => array(
          '#id' => $this->randomMachineName(),
        ),
        '#limit_allowed_plugin_ids' => $limit_allowed_plugin_ids,
        '#name' => $this->randomMachineName(),
        '#plugin_selector_id' => $plugin_selector_id,
        '#prototype_payment' => $payment,
        '#required' => (bool) mt_rand(0, 1),
      );
      $form_state = new FormState();

      $method = new \ReflectionMethod($this->sut, 'buildRefreshButton');
      $method->setAccessible(TRUE);

      $build = $method->invoke($this->sut, $element, $form_state);
      $this->assertIsArray($build);
      $this->assertTrue(is_callable($build['#ajax']['callback']));
      $this->assertSame($build['#submit'][0][0], $this->pluginDefinition['class']);
    }

    /**
     * @covers ::buildPaymentForm
     */
    public function testBuildPaymentForm() {
      $payment = $this->createMock(PaymentInterface::class);
      $payment->expects($this->atLeastOnce())
        ->method('createDuplicate')
        ->willReturnSelf();

      $element = array(
        '#parents' => array($this->randomMachineName()),
        '#prototype_payment' => $payment,
      );
      $form_state = new FormState();

      $method = new \ReflectionMethod($this->sut, 'buildPaymentForm');
      $method->setAccessible(TRUE);

      $payment = $this->createMock(PaymentInterface::class);

      $plugin_selector = $this->createMock(PluginSelectorInterface::class);
      $plugin_selector->expects($this->atLeastOnce())
        ->method('buildSelectorForm')
        ->with([], $form_state)
        ->willReturn([]);

      $configuration = [];
      $plugin_id = $this->randomMachineName();

      $entity_form_display = $this->createMock(EntityFormDisplayInterface::class);
      $entity_form_display->expects($this->once())
        ->method('buildForm')
        ->with($payment, $this->isType('array'), $form_state);

      $this->sut = $this->getMockBuilder(PaymentReferenceBase::class)
        ->setConstructorArgs(array($configuration, $plugin_id, $this->pluginDefinition, $this->requestStack, $this->paymentStorage, $this->stringTranslation, $this->dateFormatter, $this->linkGenerator, $this->renderer, $this->currentUser, $this->pluginSelectorManager, $this->paymentMethodType, new Random()))
        ->setMethods(array('getEntityFormDisplay', 'getPluginSelector'))
        ->getMockForAbstractClass();
      $this->sut->expects($this->atLeastOnce())
        ->method('getEntityFormDisplay')
        ->willReturn($entity_form_display);
      $this->sut->expects($this->atLeastOnce())
        ->method('getPluginSelector')
        ->with($element, $form_state)
        ->willReturn($plugin_selector);

      $build = $method->invoke($this->sut, $element, $form_state);
      $this->assertIsArray($build);
      $this->assertTrue(is_callable($build['pay_button']['#ajax']['callback']));
      $this->assertTrue(is_callable($build['pay_button']['#submit'][0]));
      $this->assertTrue(is_callable($build['pay_button']['#process'][0]));
      $this->assertTrue(is_callable($build['pay_link']['#process'][0]));
    }

    /**
     * @covers ::processMaxWeight
     */
    public function testProcessMaxWeight() {
      $sibling_weight_1 = mt_rand();
      $sibling_weight_2 = mt_rand();
      $form = array(
        'foo' => array(
          'bar' => array(
            'sibling_1' => array(
              '#weight' => $sibling_weight_1,
            ),
            'sibling_2' => array(
              '#weight' => $sibling_weight_2,
            ),
            'subject' => array(
              '#array_parents' => array('foo', 'bar', 'subject'),
            ),
          ),
        ),
      );
      $element = $form['foo']['bar']['subject'];
      $form_state = $this->createMock(FormStateInterface::class);

      $element_plugin = $this->sut;
      $element = $element_plugin::processMaxWeight($element, $form_state, $form);
      $this->assertGreaterThan(max($sibling_weight_1, $sibling_weight_2), $element['#weight']);
    }

    /**
     * @covers ::process
     *
     * @depends testGetInfo
     */
    public function testProcess() {
      $name = $this->randomMachineName();
      $prototype_payment = $this->createMock(PaymentInterface::class);
      $plugin_selector_id = $this->randomMachineName();
      $queue_category_id = $this->randomMachineName();
      $queue_owner_id = mt_rand();

      $element = array(
        '#default_value' => NULL,
        '#limit_allowed_plugin_ids' => NULL,
        '#name' => $name,
        '#plugin_selector_id' => $plugin_selector_id,
        '#prototype_payment' => $prototype_payment,
        '#queue_category_id' => $queue_category_id,
        '#queue_owner_id' => $queue_owner_id,
      );
      $form_state = $this->createMock(FormStateInterface::class);
      $form = [];

      $configuration = [];
      $plugin_id = $this->randomMachineName();

      $payment_form = array(
        '#foo' => $this->randomMachineName(),
      );

      $payment_view = array(
        '#foo' => $this->randomMachineName(),
      );

      $refresh_button = array(
        '#foo' => $this->randomMachineName(),
      );

      $this->sut = $this->getMockBuilder(PaymentReferenceBase::class)
        ->setConstructorArgs(array($configuration, $plugin_id, $this->pluginDefinition, $this->requestStack, $this->paymentStorage, $this->stringTranslation, $this->dateFormatter, $this->linkGenerator, $this->renderer, $this->currentUser, $this->pluginSelectorManager, $this->paymentMethodType, new Random()))
        ->setMethods(array('buildPaymentForm', 'buildPaymentView', 'buildRefreshButton'))
        ->getMockForAbstractClass();
      $this->sut->expects($this->atLeastOnce())
        ->method('buildPaymentForm')
        ->with($this->isType('array'), $form_state)
        ->willReturn($payment_form);
      $this->sut->expects($this->atLeastOnce())
        ->method('buildPaymentView')
        ->with($this->isType('array'), $form_state)
        ->willReturn($payment_view);
      $this->sut->expects($this->atLeastOnce())
        ->method('buildRefreshButton')
        ->with($this->isType('array'), $form_state)
        ->willReturn($refresh_button);
      $this->sut->expects($this->atLeastOnce())
        ->method('getPaymentQueue')
        ->willReturn($this->paymentQueue);

      $build = $this->sut->process($element, $form_state, $form);
      $this->assertTrue(is_callable($build['#element_validate'][0]));
      $this->assertTrue($build['#tree']);
      unset($build['container']['payment_form']['#access']);
      $this->assertSame($payment_form, $build['container']['payment_form']);
      unset($build['container']['payment_view']['#access']);
      $this->assertSame($payment_view, $build['container']['payment_view']);
    }

    /**
     * @covers ::process
     *
     * @dataProvider providerTestProcess
     */
    public function testProcessWithInvalidElementConfiguration(array $element) {
      $this->expectException(\InvalidArgumentException::class);
      $form_state = $this->createMock(FormStateInterface::class);
      $form = [];

      $this->sut->process($element, $form_state, $form);
    }

    /**
     * Provides data to self::testProcess().
     */
    public function providerTestProcess() {
      $name = $this->randomMachineName();
      $prototype_payment = $this->createMock(PaymentInterface::class);
      $plugin_selector_id = $this->randomMachineName();
      $queue_category_id = $this->randomMachineName();
      $queue_owner_id = mt_rand();

      $element = array(
        '#default_value' => NULL,
        '#limit_allowed_plugin_ids' => NULL,
        '#name' => $name,
        '#plugin_selector_id' => $plugin_selector_id,
        '#prototype_payment' => $prototype_payment,
        '#queue_category_id' => $queue_category_id,
        '#queue_owner_id' => $queue_owner_id,
      );

      return array(
        array(array_merge($element, array(
          '#default_value' => $this->randomMachineName(),
        ))),
        array(array_merge($element, array(
          '#limit_allowed_plugin_ids' => $this->randomMachineName(),
        ))),
        array(array_merge($element, array(
          '#queue_category_id' => mt_rand(),
        ))),
        array(array_merge($element, array(
          '#queue_owner_id' => $this->randomMachineName(),
        ))),
        array(array_merge($element, array(
          '#plugin_selector_id' => mt_rand(),
        ))),
        array(array_merge($element, array(
          '#prototype_payment' => $this->randomMachineName(),
        ))),
      );
    }

  }

}

namespace {

  if (!function_exists('drupal_process_attached')) {
    function drupal_process_attached() {
    }
  }

}
