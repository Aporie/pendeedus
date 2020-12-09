<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Type;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeManager;
use Drupal\Tests\UnitTestCase;
use Zend\Stdlib\ArrayObject;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Type\PaymentTypeManager
 *
 * @group Payment
 */
class PaymentTypeManagerTest extends UnitTestCase {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $classResolver;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $discovery;

  /**
   * The plugin factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $factory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeManager
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->classResolver = $this->createMock(ClassResolverInterface::class);

    $this->discovery = $this->createMock(DiscoveryInterface::class);

    $this->factory = $this->createMock(FactoryInterface::class);

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->cache = $this->createMock(CacheBackendInterface::class);

    $namespaces = new ArrayObject();

    $this->sut = new PaymentTypeManager($namespaces, $this->cache, $this->moduleHandler, $this->classResolver);
    $property = new \ReflectionProperty($this->sut, 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->sut, $this->discovery);
    $property = new \ReflectionProperty($this->sut, 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->sut, $this->factory);
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
      ->with('payment_type');
    $this->assertSame($definitions, $this->sut->getDefinitions());
  }

  /**
   * @covers ::getFallbackPluginId
   */
  public function testGetFallbackPluginId() {
    $plugin_id = $this->randomMachineName();
    $plugin_configuration = array($this->randomMachineName());
    $this->assertIsString( $this->sut->getFallbackPluginId($plugin_id, $plugin_configuration));
  }

}
