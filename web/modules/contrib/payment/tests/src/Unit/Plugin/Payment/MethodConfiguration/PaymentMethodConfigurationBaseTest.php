<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\MethodConfiguration;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase
 *
 * @group Payment
 */
class PaymentMethodConfigurationBaseTest extends PaymentMethodConfigurationBaseTestBase {

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->pluginDefinition = array(
      'description' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
    );
    $this->sut = $this->getMockBuilder(PaymentMethodConfigurationBase::class)
      ->setConstructorArgs(array([], '', $this->pluginDefinition, $this->stringTranslation, $this->moduleHandler))
      ->getMockForAbstractClass();
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodBase $class_name */
    $class_name = get_class($this->sut);
    $sut = $class_name::create($container, [], '', []);
    $this->assertInstanceOf(PaymentMethodConfigurationBase::class, $sut);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = $this->sut->defaultConfiguration();
    $this->assertIsArray($configuration);
    foreach (array('message_text', 'message_text_format') as $key) {
      $this->assertArrayHasKey($key, $configuration);
      $this->assertIsString( $configuration[$key]);
    }
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->assertSame([], $this->sut->calculateDependencies());
  }

  /**
   * @covers ::getMessageText
   * @covers ::setMessageText
   */
  public function testGetMessageText() {
    $message_text = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setMessageText($message_text));
    $this->assertSame($message_text, $this->sut->getMessageText());
  }

  /**
   * @covers ::getMessageTextFormat
   * @covers ::setMessageTextFormat
   */
  public function testGetMessageTextFormat() {
    $message_text_format = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setMessageTextFormat($message_text_format));
    $this->assertSame($message_text_format, $this->sut->getMessageTextFormat());
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationFormWithoutFilter() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('filter')
      ->willReturn(FALSE);

    $message_text = $this->randomMachineName();
    $this->sut->setMessageText($message_text);

    $build = $this->sut->buildConfigurationForm($form, $form_state);

    $expected_build = array(
      'message' => array(
        '#tree' => TRUE,
        '#type' => 'textarea',
        '#title' => 'Payment form message',
        '#default_value' => $message_text,
      )
    );

    $this->assertEquals($expected_build, $build);
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationFormWithFilter() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('filter')
      ->willReturn(TRUE);

    $message_text = $this->randomMachineName();
    $message_format = $this->randomMachineName();
    $this->sut->setMessageText($message_text);
    $this->sut->setMessageTextFormat($message_format);

    $build = $this->sut->buildConfigurationForm($form, $form_state);

    $expected_build = array(
      'message' => array(
        '#tree' => TRUE,
        '#type' => 'text_format',
        '#title' => 'Payment form message',
        '#default_value' => $message_text,
        '#format' => $message_format,
      )
    );

    $this->assertEquals($expected_build, $build);
  }

  /**
   * @covers ::setConfiguration
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $configuration = array(
      $this->randomMachineName() => $this->randomMachineName(),
    ) + $this->sut->defaultConfiguration();
    $return = $this->sut->setConfiguration($configuration);
    $this->assertSame(NULL, $return);
    $this->assertSame($configuration, $this->sut->getConfiguration());
  }

  /**
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationFormWithoutFilter() {
    $message_text = $this->randomMachineName();

    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('filter')
      ->willReturn(FALSE);

    $form = array(
      'message' => array(
        '#parents' => array('foo', 'bar', 'message')
      ),
    );
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('getValues')
      ->willReturn(array(
        'foo' => array(
          'bar' => array(
            'message' => $message_text,
          ),
        ),
      ));

    $this->sut->submitConfigurationForm($form, $form_state);

    $this->assertSame($message_text, $this->sut->getMessageText());
  }

  /**
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationFormWithFilter() {
    $message_text = $this->randomMachineName();
    $message_format = $this->randomMachineName();

    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('filter')
      ->willReturn(TRUE);

    $form = array(
      'message' => array(
        '#parents' => array('foo', 'bar', 'message')
      ),
    );
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('getValues')
      ->willReturn(array(
        'foo' => array(
          'bar' => array(
            'message' => array(
              'value' => $message_text,
              'format' => $message_format,
            ),
          ),
        ),
      ));

    $this->sut->submitConfigurationForm($form, $form_state);

    $this->assertSame($message_text, $this->sut->getMessageText());
    $this->assertSame($message_format, $this->sut->getMessageTextFormat());
  }

}
