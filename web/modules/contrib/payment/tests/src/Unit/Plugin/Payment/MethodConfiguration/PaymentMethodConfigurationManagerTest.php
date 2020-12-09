<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManager;
use Drupal\Tests\UnitTestCase;
use Zend\Stdlib\ArrayObject;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManager
 *
 * @group Payment
 */
class PaymentMethodConfigurationManagerTest extends UnitTestCase {

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
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManager
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->discovery = $this->createMock(DiscoveryInterface::class);

    $this->factory = $this->getMockBuilder(FactoryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->cache = $this->createMock(CacheBackendInterface::class);

    $namespaces = new ArrayObject();

    $this->sut = new PaymentMethodConfigurationManager($namespaces, $this->cache, $this->moduleHandler);
    $discovery_property = new \ReflectionProperty($this->sut, 'discovery');
    $discovery_property->setAccessible(TRUE);
    $discovery_property->setValue($this->sut, $this->discovery);
    $factory_property = new \ReflectionProperty($this->sut, 'factory');
    $factory_property->setAccessible(TRUE);
    $factory_property->setValue($this->sut, $this->factory);
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
