<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Status;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\payment\Plugin\Payment\Status\DefaultPaymentStatus;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Status\PaymentStatusManager
 *
 * @group Payment
 */
class PaymentStatusManagerTest extends UnitTestCase {

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
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManager
   */
  public $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->classResolver = $this->createMock(ClassResolverInterface::class);

    $this->discovery = $this->createMock(DiscoveryInterface::class);

    $this->factory = $this->createMock(FactoryInterface::class);

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('getModuleDirectories')
      ->willReturn([]);

    $this->cache = $this->createMock(CacheBackendInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new PaymentStatusManager($this->cache, $this->moduleHandler, $this->classResolver, $this->stringTranslation);
    $property = new \ReflectionProperty($this->sut, 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->sut, $this->discovery);
    $property = new \ReflectionProperty($this->sut, 'factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->sut, $this->factory);
  }

  /**
   * @covers ::getFallbackPluginId
   */
  public function testGetFallbackPluginId() {
    $plugin_id = $this->randomMachineName();
    $plugin_configuration = array($this->randomMachineName());
    $this->assertIsString( $this->sut->getFallbackPluginId($plugin_id, $plugin_configuration));
  }

  /**
   * @covers ::getDefinitions
   * @covers ::processDefinition
   */
  public function testGetDefinitions() {
    $discovery_definitions = array(
      'foo' => array(
        'id' => NULL,
        'parent_id' => NULL,
        'label' => $this->randomMachineName(),
        'description' => NULL,
        'operations_provider' => NULL,
        'class' => DefaultPaymentStatus::class,
      ),
    );
    $manager_definitions = $discovery_definitions;
    $manager_definitions['foo']['label'] = new TranslatableMarkup($manager_definitions['foo']['label'], [], [], $this->stringTranslation);
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($discovery_definitions);
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('payment_status');
    $this->assertEquals($manager_definitions, $this->sut->getDefinitions());
  }

  /**
   * @covers ::getChildren
   * @depends testGetDefinitions
   */
  public function testGetChildren() {
    $definitions = array(
      'foo' => array(
        'id' => 'foo',
      ),
      'bar' => array(
        'id' => 'bar',
        'parent_id' => 'foo',
      ),
    );
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertSame(array('bar'), $this->sut->getChildren('foo'));
  }

  /**
   * @covers ::getDescendants
   * @depends testGetDefinitions
   */
  public function testGetDescendants() {
    $definitions = array(
      'foo' => array(
        'id' => 'foo',
      ),
      'bar' => array(
        'id' => 'bar',
        'parent_id' => 'foo',
      ),
      'baz' => array(
        'id' => 'baz',
        'parent_id' => 'bar',
      ),
    );
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertSame(array('bar', 'baz'), $this->sut->getDescendants('foo'));
  }

  /**
   * @covers ::getAncestors
   * @depends testGetDefinitions
   */
  public function testGetAncestors() {
    $definitions = array(
      'foo' => array(
        'id' => 'foo',
      ),
      'bar' => array(
        'id' => 'bar',
        'parent_id' => 'foo',
      ),
      'baz' => array(
        'id' => 'baz',
        'parent_id' => 'bar',
      ),
    );
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertSame(array('bar', 'foo'), $this->sut->getAncestors('baz'));
  }

  /**
   * @covers ::hasAncestor
   * @depends testGetDefinitions
   */
  public function testHasAncestor() {
    $definitions = array(
      'foo' => array(
        'id' => 'foo',
      ),
      'bar' => array(
        'id' => 'bar',
        'parent_id' => 'foo',
      ),
      'baz' => array(
        'id' => 'baz',
        'parent_id' => 'bar',
      ),
    );
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertTrue($this->sut->hasAncestor('baz', 'foo'));
    $this->assertFalse($this->sut->hasAncestor('baz', 'baz'));
  }

  /**
   * @covers ::isOrHasAncestor
   * @depends testGetDefinitions
   */
  public function testIsOrHasAncestor() {
    $definitions = array(
      'foo' => array(
        'id' => 'foo',
      ),
      'bar' => array(
        'id' => 'bar',
        'parent_id' => 'foo',
      ),
      'baz' => array(
        'id' => 'baz',
        'parent_id' => 'bar',
      ),
    );
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertTrue($this->sut->isOrHasAncestor('baz', 'foo'));
    $this->assertTrue($this->sut->isOrHasAncestor('baz', 'baz'));
  }
}
