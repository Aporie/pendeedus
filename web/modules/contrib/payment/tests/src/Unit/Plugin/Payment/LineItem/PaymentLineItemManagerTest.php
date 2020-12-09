<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\LineItem;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManager;
use Drupal\Tests\UnitTestCase;
use Zend\Stdlib\ArrayObject;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManager
 *
 * @group Payment
 */
class PaymentLineItemManagerTest extends UnitTestCase {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $discovery;

  /**
   * The plugin factory.
   *
   * @var \Drupal\Component\Plugin\Factory\DefaultFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $factory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The payment line_item plugin manager under test.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface
   */
  public $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->discovery = $this->createMock(DiscoveryInterface::class);

    $this->factory = $this->getMockBuilder(DefaultFactory::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->cache = $this->createMock(CacheBackendInterface::class);

    $namespaces = new ArrayObject();

    $this->sut = new PaymentLineItemManager($namespaces, $this->cache, $this->moduleHandler);
    $discovery_property = new \ReflectionProperty($this->sut, 'discovery');
    $discovery_property->setAccessible(TRUE);
    $discovery_property->setValue($this->sut, $this->discovery);
    $factory_property = new \ReflectionProperty($this->sut, 'factory');
    $factory_property->setAccessible(TRUE);
    $factory_property->setValue($this->sut, $this->factory);
  }

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    $definitions = array(
      'foo' => array(
        'label' => $this->randomMachineName(),
      ),
    );
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('payment_line_item');
    $this->assertSame($definitions, $this->sut->getDefinitions());
  }

}
