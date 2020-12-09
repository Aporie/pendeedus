<?php

namespace Drupal\Tests\payment\Unit\Element;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\payment\Element\PaymentStatusesDisplay;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Element\PaymentStatusesDisplay
 *
 * @group Payment
 */
class PaymentStatusesDisplayTest extends UnitTestCase {

  /**
   * The fate formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $dateFormatter;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Element\PaymentStatusesDisplay
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->dateFormatter = $this->getMockBuilder(DateFormatter::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->stringTranslation = $this->getStringTranslationStub();

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new PaymentStatusesDisplay($configuration, $plugin_id, $plugin_definition, $this->stringTranslation, $this->dateFormatter);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('date.formatter', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->dateFormatter),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $sut = PaymentStatusesDisplay::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentStatusesDisplay::class, $sut);
  }

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $info = $this->sut->getInfo();
    $this->assertIsArray($info);
    foreach ($info['#pre_render'] as $callback) {
      $this->assertTrue(is_callable($callback));
    }
  }

  /**
   * @covers ::preRender
   */
  public function testPreRender() {
    $payment_status_created = mt_rand();
    $payment_status = $this->createMock(PaymentStatusInterface::class);
    $payment_status->expects($this->atLeastOnce())
      ->method('getCreated')
      ->willReturn($payment_status_created);
    $payment_status->expects($this->atLeastOnce())
      ->method('getPluginDefinition')
      ->willReturn(['label' => 'Example']);

    $this->dateFormatter->expects($this->once())
      ->method('format')
      ->with($payment_status_created);

    $element = array(
      '#payment_statuses' => [$payment_status],
    );

    $build = $this->sut->preRender($element);
    $this->assertSame('table', $build['table']['#type']);
  }

  /**
   * @covers ::preRender
   */
  public function testPreRenderWithoutPayment() {
    $this->expectException(\InvalidArgumentException::class);
    $element = [];

    $this->sut->preRender($element);
  }

}
