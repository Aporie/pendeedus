<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Method;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\payment\Plugin\Payment\Method\BasicDeriver;
use Drupal\payment\Plugin\Payment\MethodConfiguration\Basic;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Method\BasicDeriver
 *
 * @group Payment
 */
class BasicDeriverTest extends UnitTestCase {

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
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\BasicDeriver
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->paymentMethodConfigurationManager = $this->createMock(PaymentMethodConfigurationManagerInterface::class);

    $this->paymentMethodConfigurationStorage = $this->createMock(EntityStorageInterface::class);

    $this->sut = new BasicDeriver($this->paymentMethodConfigurationStorage, $this->paymentMethodConfigurationManager);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('payment_method_configuration')
      ->willReturn($this->paymentMethodConfigurationStorage);

    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('entity_type.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_type_manager),
      array('plugin.manager.payment.method_configuration', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentMethodConfigurationManager),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = BasicDeriver::create($container, [], '', []);
    $this->assertInstanceOf(BasicDeriver::class, $sut);
  }

  /**
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions() {
    $id_enabled_basic = $this->randomMachineName();
    $id_disabled_basic = $this->randomMachineName();
    $brand_label = $this->randomMachineName();
    $message_text = $this->randomMachineName();
    $message_text_format = $this->randomMachineName();
    $execute_status_id = $this->randomMachineName();
    $capture = TRUE;
    $capture_status_id = $this->randomMachineName();
    $refund = TRUE;
    $refund_status_id = $this->randomMachineName();

    $payment_method_enabled_basic = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_enabled_basic->expects($this->any())
      ->method('status')
      ->willReturn(TRUE);
    $payment_method_enabled_basic->expects($this->any())
      ->method('id')
      ->willReturn($id_enabled_basic);
    $payment_method_enabled_basic->expects($this->any())
      ->method('getPluginConfiguration')
      ->willReturn([
        'brand_label' => $brand_label,
        'message_text' => $message_text,
        'message_text_format' => $message_text_format,
        'execute_status_id' => $execute_status_id,
        'capture' => $capture,
        'capture_status_id' => $capture_status_id,
        'refund' => $refund,
        'refund_status_id' => $refund_status_id,
      ]);
    $payment_method_enabled_basic->expects($this->any())
      ->method('getPluginId')
      ->willReturn('payment_basic');

    $payment_method_disabled_basic = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_disabled_basic->expects($this->any())
      ->method('status')
      ->willReturn(FALSE);
    $payment_method_disabled_basic->expects($this->any())
      ->method('id')
      ->willReturn($id_disabled_basic);
    $payment_method_disabled_basic->expects($this->any())
      ->method('getPluginConfiguration')
      ->willReturn([
        'brand_label' => $brand_label,
        'message_text' => $message_text,
        'message_text_format' => $message_text_format,
        'execute_status_id' => $execute_status_id,
        'capture' => $capture,
        'capture_status_id' => $capture_status_id,
        'refund' => $refund,
        'refund_status_id' => $refund_status_id,
      ]);
    $payment_method_disabled_basic->expects($this->any())
      ->method('getPluginId')
      ->willReturn('payment_basic');

    $payment_method_enabled_no_basic = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_enabled_no_basic->expects($this->any())
      ->method('status')
      ->willReturn(TRUE);
    $payment_method_enabled_no_basic->expects($this->any())
      ->method('getPluginId')
      ->willReturn($this->randomMachineName());

    $this->paymentMethodConfigurationStorage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(array($payment_method_enabled_basic, $payment_method_enabled_no_basic, $payment_method_disabled_basic));

    $payment_method_plugin = $this->getMockBuilder(Basic::class)
      ->disableOriginalConstructor()
      ->getMock();
    $payment_method_plugin->expects($this->any())
      ->method('getBrandLabel')
      ->willReturn($brand_label);
    $payment_method_plugin->expects($this->any())
      ->method('getMessageText')
      ->willReturn($message_text);
    $payment_method_plugin->expects($this->any())
      ->method('getMessageTextFormat')
      ->willReturn($message_text_format);
    $payment_method_plugin->expects($this->any())
      ->method('getExecuteStatusId')
      ->willReturn($execute_status_id);
    $payment_method_plugin->expects($this->any())
      ->method('getCaptureStatusId')
      ->willReturn($capture_status_id);
    $payment_method_plugin->expects($this->any())
      ->method('getCapture')
      ->willReturn($capture);
    $payment_method_plugin->expects($this->any())
      ->method('getRefundStatusId')
      ->willReturn($refund_status_id);
    $payment_method_plugin->expects($this->any())
      ->method('getRefund')
      ->willReturn($refund);

    $this->paymentMethodConfigurationManager->expects($this->any())
      ->method('createInstance')
      ->with('payment_basic')
      ->willReturn($payment_method_plugin);

    $class = $this->randomMachineName();
    $derivatives = $this->sut->getDerivativeDefinitions(array(
      'class' => $class,
      'id' => $this->randomMachineName(),
    ));
    $this->assertIsArray($derivatives);
    $this->assertCount(2, $derivatives);
    $map = array(
      $id_enabled_basic => TRUE,
      $id_disabled_basic => FALSE,
    );
    foreach ($map as $id => $active) {
      $this->assertArrayHasKey($id, $derivatives);
      $this->assertArrayHasKey('active', $derivatives[$id]);
      $this->assertSame($active, $derivatives[$id]['active']);
      $this->assertArrayHasKey('class', $derivatives[$id]);
      $this->assertSame($class, $derivatives[$id]['class']);
      $this->assertArrayHasKey('label', $derivatives[$id]);
      $this->assertSame($brand_label, $derivatives[$id]['label']);
      $this->assertArrayHasKey('message_text', $derivatives[$id]);
      $this->assertSame($message_text, $derivatives[$id]['message_text']);
      $this->assertArrayHasKey('message_text_format', $derivatives[$id]);
      $this->assertSame($message_text_format, $derivatives[$id]['message_text_format']);
      $this->assertArrayHasKey('execute_status_id', $derivatives[$id]);
      $this->assertSame($execute_status_id, $derivatives[$id]['execute_status_id']);
      $this->assertArrayHasKey('capture', $derivatives[$id]);
      $this->assertSame($capture, $derivatives[$id]['capture']);
      $this->assertArrayHasKey('capture_status_id', $derivatives[$id]);
      $this->assertSame($capture_status_id, $derivatives[$id]['capture_status_id']);
    }
  }
}
