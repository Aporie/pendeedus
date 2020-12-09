<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\LineItem;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Plugin\Payment\LineItem\Basic;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\LineItem\Basic
 *
 * @group Payment
 */
class BasicTest extends UnitTestCase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\Basic
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->database = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->stringTranslation = $this->getStringTranslationStub();

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new Basic($configuration, $plugin_id, $plugin_definition, $this->stringTranslation, $this->database);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('database', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->database),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = Basic::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(Basic::class, $sut);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = array(
      'currency_code' => NULL,
      'name' => NULL,
      'quantity' => 1,
      'amount' => 0,
      'description' => NULL,
    );
    $this->assertEquals($configuration, $this->sut->defaultConfiguration());
  }

  /**
   * @covers ::setAmount
   * @covers ::getAmount
   */
  public function testGetAmount() {
    $amount = mt_rand();
    $this->assertSame($this->sut, $this->sut->setAmount($amount));
    $this->assertSame($amount, $this->sut->getAmount());
  }

  /**
   * @covers ::setCurrencyCode
   * @covers ::getCurrencyCode
   */
  public function testGetCurrencyCode() {
    $currency_code = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setCurrencyCode($currency_code));
    $this->assertSame($currency_code, $this->sut->getCurrencyCode());
  }

  /**
   * @covers ::setDescription
   * @covers ::getDescription
   */
  public function testGetDescription() {
    $description = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setDescription($description));
    $this->assertSame($description, $this->sut->getDescription());
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $form_elements = $this->sut->buildConfigurationForm($form, $form_state);
    $this->assertIsArray($form_elements);
  }

  /**
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationForm() {
    $amount = mt_rand();
    $currency_code = $this->randomMachineName(3);
    $description = $this->randomMachineName();
    $name = $this->randomMachineName();
    $payment_id = mt_rand();
    $quantity = mt_rand();

    $form = array(
      '#parents' => array('foo', 'bar'),
    );
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('getValues')
      ->willReturn(array(
        'foo' => array(
          'bar' => array(
            'amount' => array(
              'amount' => $amount,
              'currency_code' => $currency_code,
            ),
            'description' => $description,
            'name' => $name,
            'payment_id' => $payment_id,
            'quantity' => $quantity,
          ),
        ),
      ));
    $this->sut->submitConfigurationForm($form, $form_state);

    $this->assertSame($amount, $this->sut->getAmount());
    $this->assertSame($currency_code, $this->sut->getCurrencyCode());
    $this->assertSame($description, $this->sut->getDescription());
    $this->assertSame($name, $this->sut->getName());
    $this->assertSame($quantity, $this->sut->getQuantity());
  }

}
