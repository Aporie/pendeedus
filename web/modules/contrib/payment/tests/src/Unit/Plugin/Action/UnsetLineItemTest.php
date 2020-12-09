<?php

namespace Drupal\Tests\payment\Unit\Plugin\Action;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Action\UnsetLineItem;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Action\UnsetLineItem
 *
 * @group Payment
 */
class UnsetLineItemTest extends UnitTestCase {

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Action\UnsetLineItem
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->stringTranslation = $this->getStringTranslationStub();

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $this->sut = new UnsetLineItem($configuration, $plugin_id, $plugin_definition, $this->stringTranslation);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = UnsetLineItem::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(UnsetLineItem::class, $sut);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = $this->sut->defaultConfiguration();
    $this->assertIsArray($configuration);
    $this->assertArrayHasKey('line_item_name', $configuration);
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $form = $this->sut->buildConfigurationForm($form, $form_state);
    $this->assertIsArray($form);
    $this->assertArrayHasKey('line_item_name', $form);
  }

  /**
   * @covers ::submitConfigurationForm
   * @depends testBuildConfigurationForm
   */
  public function testSubmitConfigurationForm() {
    $name = $this->randomMachineName();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('getValues')
      ->willReturn(array(
        'line_item_name' => $name,
      ));
    $this->sut->submitConfigurationForm($form, $form_state);
    $configuration = $this->sut->getConfiguration();
    $this->assertSame($name, $configuration['line_item_name']);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $name = $this->randomMachineName();

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->once())
      ->method('unsetLineItem')
      ->with($name);

    $this->sut->setConfiguration(array(
      'line_item_name' => $name,
    ));

    $this->sut->execute($payment);
  }

  /**
   * @covers ::access
   */
  public function testAccessWithPaymentAsObject() {
    $account = $this->createMock(AccountInterface::class);

    $access_result = new AccessResultAllowed();

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->atLeastOnce())
      ->method('access')
      ->with('update', $account, TRUE)
      ->willReturn($access_result);

    $this->assertSame($access_result, $this->sut->access($payment, $account, TRUE));
  }

  /**
   * @covers ::access
   */
  public function testAccessWithPaymentAsBoolean() {
    $account = $this->createMock(AccountInterface::class);

    $payment = $this->createMock(PaymentInterface::class);
    $payment->expects($this->atLeastOnce())
      ->method('access')
      ->with('update', $account)
      ->willReturn(TRUE);

    $this->assertTrue($this->sut->access($payment, $account));
  }

  /**
   * @covers ::access
   */
  public function testAccessWithoutPaymentAsObject() {
    $account = $this->createMock(AccountInterface::class);

    $access_result = $this->sut->access(NULL, $account, TRUE);
    $this->assertFalse($access_result->isAllowed());
  }

  /**
   * @covers ::access
   */
  public function testAccessWithoutPaymentAsBoolean() {
    $account = $this->createMock(AccountInterface::class);

    $access_result = $this->sut->access(NULL, $account);
    $this->assertFalse($access_result);
  }

}
